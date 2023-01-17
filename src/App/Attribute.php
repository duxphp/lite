<?php
declare(strict_types=1);

namespace Dux\App;

use Dux\App;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;

class Attribute {

    static function load(array $apps): array {
        $status = App::config("app")->get("app.cache", false);
        $cachePath = data_path("cache/app/attribute.json");
        if (!$status) {
            return self::get($apps);
        }
        if (is_file($cachePath)) {
            $content = FileSystem::read($cachePath);
            return json_decode($content, true);
        }
        $data = self::get($apps);
        FileSystem::write($cachePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $data;
    }

    static function get($apps): array {
        $data = [];
        foreach ($apps as $vo) {
            $reflection = new \ReflectionClass($vo);
            $appDir = dirname($reflection->getFileName());
            $files = Finder::findFiles("*/*.php")->from($appDir);
            foreach ($files as $file) {
                $class = $reflection->getNamespaceName() . "\\" . basename($file->getPath()) . "\\" . $file->getBasename(".php");
                if (!class_exists($class)) {
                    continue;
                }
                $reflection = new \ReflectionClass($class);
                $attributes = $reflection->getAttributes();
                foreach ($attributes as $attribute) {
                    if (!isset($data[$attribute->getName()]) && !class_exists($attribute->getName())) {
                        continue;
                    }
                    $data[$attribute->getName()][] = [
                        "class" => $class,
                        "params" => $attribute->getArguments()
                    ];
                }
                $methods = $reflection->getMethods();
                foreach ($methods as $method) {
                    $attributes = $method->getAttributes();
                    foreach ($attributes as $attribute) {
                        if (!isset($data[$attribute->getName()]) && !class_exists($attribute->getName())) {
                            continue;
                        }
                        $data[$attribute->getName()][] = [
                            "class" => $class . ":" . $method->getName(),
                            "params" => $attribute->getArguments()
                        ];
                    }
                }
            }
        }
        return $data;
    }
}