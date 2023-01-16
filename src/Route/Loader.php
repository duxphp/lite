<?php

namespace Dux\Route;

use Dux\App;
use Dux\Route\Attribute\Route;
use Dux\Route\Attribute\Group;
use Dux\Route\Attribute\Manage;
use Nette\Utils\Finder;

class Loader {

    public static function run($paths): void {
        foreach ($paths as $namespace => $path) {
            $files = Finder::findFiles("*.php")->in($path);
            foreach ($files as $file) {
                // file
                $className = $namespace . "\\" . $file->getBasename(".php");
                $ref = new \ReflectionClass($className);
                // class
                $attributes = $ref->getAttributes();
                $group = null;
                foreach ($attributes as $attribute) {
                    $class = $attribute->newInstance();
                    if (!$class instanceof Manage
                        && !$class instanceof Group
                        && !$class instanceof Route
                    ) {
                        continue;
                    }
                    $info = $class->get();
                    if (empty($info["app"])) {
                        throw new \Exception("class [" . $ref->getName() . "] attribute parameter missing \"app\" ");
                    }
                    // route
                    if ($class instanceof Route) {
                        App::$bootstrap->getRoute()->get($info["app"])->map(
                            methods: $info["methods"],
                            pattern: $info["pattern"],
                            callable: $ref->getName(),
                            name: $info["name"],
                            title: $info["title"],
                            permission: $info["permission"]);
                    }
                    // manage
                    if ($class instanceof Manage) {
                        App::$bootstrap->getRoute()->get($info["app"])->manage(
                            pattern: $info["pattern"],
                            class: $ref->getName(),
                            name: $info["name"],
                            title: $info["title"],
                            ways: $info["ways"],
                            permission: $info["permission"]
                        );
                    }
                    // group
                    if ($class instanceof Group) {
                        $group = App::$bootstrap->getRoute()->get($info["app"])->group($info["pattern"], $info["title"], ...$info["middleware"]);
                    }
                }

                // methods
                $methods = $ref->getMethods();
                foreach ($methods as $method) {
                    $methodAttr = $method->getAttributes();
                    foreach ($methodAttr as $attr) {
                        $class = $attr->newInstance();
                        if (!$class instanceof Route) {
                            continue;
                        }
                        $info = $class->get();
                        if (!$group) {
                            $app = App::$bootstrap->getRoute()->get($info["app"]);
                            if (empty($info["app"])) {
                                throw new \Exception("method [" . $ref->getName() . ":" . $method->getName() . "] attribute parameter missing \"app\" ");
                            }
                        } else {
                            $app = $group;
                        }
                        $app->map(
                            methods: $info["methods"],
                            pattern: $info["pattern"],
                            callable: $ref->getName(),
                            name: $info["name"],
                            title: $info["title"],
                            permission: $info["permission"]);
                    }
                }
            }
        }
    }
}