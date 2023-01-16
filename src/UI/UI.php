<?php
declare(strict_types=1);

namespace Dux\UI;

use Dux\App;
use Nette\Utils\FileSystem;

class UI {

    static array $dirList = [];

    static function register(string $dir, string $app): void {
        self::$dirList[] = [
            "path" => realpath($dir),
            "app" => $app
        ];
    }

    static function sync(): void {
        foreach (self::$dirList as $dir) {
            $files = [];
            self::getFiles($dir["path"], $files);
            $dirName = basename($dir["path"]);
            $uiPath = "{$dir['app']}/$dirName";

            foreach ($files as $file) {
                $uiFile = self::getUiPath($uiPath . str_replace($dir, "", $file));
                if (!is_file($uiFile)) {
                    FileSystem::copy($file, $uiFile);
                    continue;
                }
                if (filemtime($file) <= filemtime($uiFile)) {
                    continue;
                }
                FileSystem::copy($file, $uiFile);
            }
        }

    }

    static private function getUiPath(string $dir): string {
        return App::$basePath . "/client/app/$dir";
    }

    static private function getFiles($path, &$data): void {
        $dirIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

        foreach ($dirIterator as $info) {
            if ($info->getPath() == $path) {
                continue;
            }
            if ($info->isDir()) {
                self::getFiles($info->getPath(), $data);
            } else {
                $data[] = $info->getPath() . "/" . $info->getFilename();
            }
        }
    }

}