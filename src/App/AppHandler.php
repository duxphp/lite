<?php
declare(strict_types=1);

namespace Dux\App;

use Dux\App;
use Nette\Utils\FileSystem;
use Noodlehaus\Config;

class AppHandler
{

    public static function install(string $name): void
    {
        $dir = base_path("vendor/$name");
        if (!is_dir($dir)) {
            throw new \ErrorException('The application already exists');
        }
        $composerFile = $dir . '/composer.json';
        if (!is_file($composerFile)) {
            throw new \ErrorException('The application configuration does not exist');

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
                echo "  - Add $relativeDir \n";
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
    }

    public static function uninstall(string $name): void
    {
        $dir = base_path("vendor/$name");
        if (!is_dir($dir)) {
            throw new \ErrorException('The application already exists');
        }
        $composerFile = $dir . '/composer.json';
        if (!is_file($composerFile)) {
            throw new \ErrorException('The application configuration does not exist');
        }
        $config = json_decode(file_get_contents($composerFile), true);
        $extra = $config['extra'];
        $duxExtra = $extra['dux'] ?: [];

        $apps = [];
        foreach ($duxExtra as $item) {
            $target = $item['target'];
            $source = $item['source'];
            $list = glob("$dir/$source/*");
            foreach ($list as $vo) {
                $apps[] = basename($vo);
                $relativeDir = $target . "/" . basename($vo);
                $targetDir = base_path($relativeDir);
                FileSystem::delete($targetDir);
                echo "  - Delete $relativeDir \n";
            }
        }

        // config
        $configFile = App::$configPath . "/app.yaml";
        $conf = Config::load($configFile);
        $registers = $conf->get("registers", []);

        foreach ($registers as $key => $vo) {
            $params = explode('\\', $vo);
            $app = $params[1];
            if (!in_array($app, $apps)) {
                continue;
            }
            unset($registers[$key]);
        }

        $conf->set("registers", array_values($registers));
        $conf->toFile($configFile);
    }

}