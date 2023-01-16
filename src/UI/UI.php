<?php
declare(strict_types=1);


use Dux\App;
use Nette\Utils\FileSystem;

class UI {

    static $dirList = [];

    static function register(string $dir) {
        self::$dirList[] = $dir;
    }

    static function sync(): void {
        foreach (self::$dirList as $dir) {
            $files = [];
            self::getFiles($dir, $files);
            $dirName = lcfirst(dirname($dir));
            foreach ($files as $file) {
                $uiFile = self::getUiPath("/$dirName/" . str_replace($dir, "", $file));
                if (!is_file($uiFile)) {
                    FileSystem::copy($file, $uiFile);
                    continue;
                }
                $info = new DirectoryIterator($file);
                $uInfo = new DirectoryIterator($uiFile);
                if ($info->getCTime() <= $uInfo->getCTime()) {
                    continue;
                }
                FileSystem::copy($file, $uiFile);
            }
        }

    }

    static function getUiPath(string $dir): string {
        return App::$basePath . "/client/app" . $dir;
    }

    static function getFiles($path, &$data) {
        $dirIterator = new DirectoryIterator($path);
        foreach ($dirIterator as $info) {
            if ($info->isDir()) {
                self::getFiles($info->getPath(), $data);
            }else {
                $data[] = $info->getPath();
            }
        }
    }

}