<?php
declare(strict_types=1);

namespace Dux\App;

use Dux\App;
use Nette\Utils\FileSystem;
use Noodlehaus\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppInstallCommand extends Command
{

    protected static $defaultName = 'app:install';
    protected static $defaultDescription = 'install applications in the system';


    protected function configure(): void
    {
        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            'please enter the package name'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $dir = base_path("vendor/$name");
        if (!is_dir($dir)) {
            return $this->error($output, 'The application already exists');
        }
        $composerFile = $dir . '/composer.json';
        if (!is_file($composerFile)) {
            return $this->error($output, 'The application configuration does not exist');
        }
        $config = json_decode(file_get_contents($composerFile), true);
        $extra = $config['extra'];
        $duxExtra = $extra['dux'] ?: [];

        $apps = [];
        foreach ($duxExtra as $item) {

            $target = $item['target'];
            $source = $item['source'];
            $ignore = (bool)$item['ignore'];

            $list = glob("$dir/$source/*");
            foreach ($list as $vo) {
                $apps[] = basename($vo);
                $relativeDir = $target . "/" . basename($vo);
                $targetDir = base_path($relativeDir);
                if ($ignore && (is_dir($targetDir) || is_file($targetDir))) {
                    continue;
                }
                FileSystem::copy($vo, $targetDir);
                $output->writeln("<info>  - Add $relativeDir </info>");
            }
        }

        // config
        $configFile = App::$configPath . "/app.yaml";
        $conf = Config::load($configFile);
        $registers = $conf->get("registers", []);
        foreach ($apps as $app) {
            $name = "\\App\\$app\\App";
            if (in_array($name, $registers)) {
                continue;
            }
            $registers[] = $name;
        }
        $conf->set("registers", $registers);
        $conf->toFile($configFile);

        $output->writeln("<info>successfully installing the application</info>");
        return Command::SUCCESS;
    }

    public function error(OutputInterface $output, string $message): int
    {
        $output->writeln("<error>$message</error>");
        return Command::FAILURE;
    }

}