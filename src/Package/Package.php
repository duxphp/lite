<?php

namespace Dux\Package;

use Dux\Handlers\Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Collection;
use Nette\Utils\FileSystem;
use Noodlehaus\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Package
{
    public static function downloadPackages(InputInterface $input, OutputInterface $output, Collection $packages, Collection $apps, Collection $composers, Collection $node, Collection $files, array $data): void
    {
        foreach ($data as $item) {
            [$config, $appFiles] = self::download($input, $output, $item);
            $key = $packages->search(function ($vo) use ($item) {
                return $item['name'] == $vo['name'];
            });
            if ($key !== false) {
                $packages->forget($key);
            }
            $packages->add($config);
            $apps->add($item['app']);

            foreach ($appFiles as $source => $target) {
                $files->put($source, $target);
            }
            $phpDependencies = $config['phpDependencies'] ?: [];
            foreach ($phpDependencies as $key => $vo) {
                if ($composers->has($key)) {
                    continue;
                }
                $composers->put($key, $vo);
            }
            $jsDependencies = $config['jsDependencies'] ?: [];
            foreach ($jsDependencies as $key => $vo) {
                if ($node->has($key)) {
                    continue;
                }
                $node->put($key, $vo);
            }
        }
    }

    public static function download(InputInterface $input, OutputInterface $output, array $data): array
    {
        $client = new Client();
        $packageDir = data_path('package');
        $packageFile = $packageDir . '/' . $data['md5'] . '.zip';

        $packageFileDir = data_path('package/' . $data['md5']);
        $appDir = $packageFileDir . '/app';
        $jsDir = $packageFileDir . '/js';
        $appConfigFile = $appDir . '/app.json';
        $configFile = $packageFileDir . '/config.yaml';

        if (!$data['app']) {
            throw new Exception('App name not found');
        }

        FileSystem::createDir($packageDir);
        FileSystem::createDir($packageFileDir);

        $progressBar = new ProgressBar($output);
        $progressBar->setFormat('Download %message%: %current%/%max% [%bar%] %percent:3s%%');
        $progressBar->setMessage($data['name']);
        $progressBar->start();
        $response = $client->get($data['url'], [
            'sink' => $packageFile,
            'progress' => function (
                $downloadTotal,
                $downloadedBytes,
                $uploadTotal,
                $uploadedBytes
            ) use ($output, $data, $progressBar) {
                $progressBar->setMaxSteps($downloadTotal);
                $progressBar->setProgress($downloadedBytes);
            },
        ]);

        $output->writeln('');

        if ($response->getStatusCode() != 200) {
            throw new Exception('Failed to download application');
        }

        $fileMd5 = md5_file($packageFile);
        if ($data['md5'] != $fileMd5) {
            throw new Exception('File verification failed');
        }

        $zip = new \ZipArchive;
        $res = $zip->open($packageFile);
        if (!$res) {
            throw new Exception('File cannot be opened');
        }
        if (!$zip->extractTo($packageFileDir)) {
            throw new Exception('Corrupted file decompression failure');
        }

        $config = self::getJson($appConfigFile);

        $copyMaps = [
            $appDir => app_path(ucfirst($data['app'])),
        ];
        if (is_dir($jsDir)) {
            $copyMaps[$jsDir] = base_path('web/src/pages/' . $data['app']);
        }
        if (is_file($configFile)) {
            $copyMaps[$configFile] = config_path($data['app'] . '.yaml');
        }

        return [
            $config,
            $copyMaps
        ];
    }

    public static function query(string $username, string $password, array $queryData, SymfonyStyle $io)
    {
        $client = new Client();
        try {
            $response = $client->post('http://cloud.test/v/package/version/query', [
                'query' => [
                    'type' => 'php',
                    'download' => true
                ],
                'json' => $queryData,
//                [
//                    $name => $verType ?: 'release'
//                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'auth' => [$username, $password],
            ]);
            $content = $response->getBody()?->getContents();
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $content = $response->getBody()?->getContents();
        }
        if ($response->getStatusCode() == 401) {
            $io->error('[CLOUD] Wrong username and password');
            return Command::FAILURE;
        }
        $responseData = json_decode($content ?: '', true);
        if ($response->getStatusCode() !== 200) {
            $io->warning('[CLOUD] ' . $response->getStatusCode() . ' ' . ($responseData['message'] ?: 'Server connection failed'));
            return Command::FAILURE;
        }
        $appData = $responseData['data'];
        if (!$appData) {
            $io->warning('[CLOUD] Application data does not exist');
            return Command::FAILURE;
        }
        return $appData;
    }

    public static function composer(OutputInterface $output, array $maps): void
    {
        if (!$maps) {
            return;
        }

        $file = base_path('composer.json');

        if (!is_file($file)) {
            throw new Exception('composer.json file does not exist');
        }

        $fileData = self::getJson($file);
        $require = $fileData['require'];
        if (!$require) {
            throw new Exception('composer require empty');
        }
        $requireNames = array_keys($require);

        foreach ($maps as $key => $vo) {
            if (in_array($key, $requireNames)) {
                continue;
            }
            $require[$key] = $vo;
        }

        $fileData['require'] = $require;

        self::saveJson($file, $fileData);
    }

    public static function node(OutputInterface $output, array $maps): void
    {
        if (!$maps) {
            return;
        }

        $file = base_path('web/package.json');

        if (!is_file($file)) {
            throw new Exception('package.json file does not exist');
        }

        $fileData = self::getJson($file);
        $dependencies = $fileData['dependencies'];
        if (!$dependencies) {
            throw new Exception('package require empty');
        }
        $dependenciesNames = array_keys($dependencies);

        foreach ($maps as $key => $vo) {
            if (in_array($key, $dependenciesNames)) {
                continue;
            }
            $dependencies[$key] = $vo;
        }

        $fileData['dependencies'] = $dependencies;

        self::saveJson($file, $fileData);
    }

    public static function copy(OutputInterface $output, array $maps): void
    {
        foreach ($maps as $source => $target) {
            $output->writeln('Copy: ' . str_replace(base_path(), '', $target));
            FileSystem::copy($source, $target);
        }
    }

    public static function saveConfig(OutputInterface $output, array $apps): void
    {
        if (!$apps) {
            return;
        }
        $configFile = config_path('app.yaml');
        $conf = Config::load($configFile);
        $registers = $conf->get("registers", []);
        foreach ($apps as $app) {
            $app = ucfirst($app);
            $name = "\\App\\$app\\App";
            if (in_array($name, (array)$registers)) {
                continue;
            }
            $registers[] = $name;
        }
        $output->writeln('Configuring Application Injection');
        $conf->set("registers", $registers);
        $conf->toFile($configFile);
    }

    public static function getJson(string $file): array
    {

        $name = str_replace(base_path(), '', $file);
        $content = file_get_contents($file);
        if ($content === false) {
            throw new Exception('Failed to read file ' . $name);
        }
        return json_decode((string)$content, true) ?: [];
    }

    public static function saveJson(string $file, array $data): void
    {

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (!file_put_contents($file, $json)) {
            throw new Exception('composer.json No permission to edit');
        }
    }


}