<?php
declare(strict_types=1);

namespace Dux\UI;

use Dux\App;
use Nette\Utils\FileSystem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class UI
{

    public static array $dirList = [];

    public static function register(string $dir, string $app): void
    {
        self::$dirList[] = [
            "path" => realpath($dir),
            "app" => $app
        ];
    }

    public static function sync(): void
    {
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

    private static function getUiPath(string $dir): string
    {
        return App::$basePath . "/web/pages/$dir";
    }

    private static function getFiles($path, &$data): void
    {
        $dirIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

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