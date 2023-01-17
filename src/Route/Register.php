<?php
declare(strict_types=1);

namespace Dux\Route;

use Dux\App;
use Dux\Handlers\Exception;
use Dux\Route\Attribute\Group;
use Dux\Route\Attribute\Manage;

class Register {

    public array $app = [];
    public array $path = [];

    /**
     * 设置路由应用
     * @param string $name
     * @param Route $route
     * @return void
     */
    public function set(string $name, Route $route): void {
        $this->app[$name] = $route;
    }

    /**
     * 获取路由应用
     * @param string $name
     * @return Route
     */
    public function get(string $name): Route {

        if (!isset($this->app[$name])) {
            throw new Exception("The routing app [$name] is not registered");
        }
        return $this->app[$name];
    }

    /**
     * 注册注解路由
     * @param string $namespace
     * @param string $path
     * @return void
     */
    public function load(string $namespace, string $path): void {
        $this->path[$namespace] = $path;
    }

    /**
     * 注解路由运行
     * @return void
     */
    public function run(Register $route) {
        $groupClass = [];
        $attributes = (array) App::di()->get("attributes");
        foreach ($attributes as $attribute => $list) {
            foreach ($list as $vo) {
                if (
                    $attribute != Manage::class &&
                    $attribute != Group::class &&
                    $attribute != \Dux\Route\Attribute\Route::class
                ) {
                    continue;
                }
                $params = $vo["params"];
                $class = $vo["class"];
                // route
                if ($attribute == \Dux\Route\Attribute\Route::class) {
                    $group = null;
                    if (str_contains($class, ":")) {
                        // method
                        [$className, $methodName] = explode(":", $class, 2);
                        if (!$params["app"] && !isset($groupClass[$className])) {
                            throw new \Exception("class [" . $class . "] attribute parameter missing \"app\" ");
                        }
                        $group = $params["app"] ? $route->get($params["app"]) : $groupClass[$className];
                    } else {
                        // class
                        if (empty($params["app"])) {
                            throw new \Exception("class [" . $class . "] attribute parameter missing \"app\" ");
                        }
                        $group = $route->get($params["app"]);
                    }
                    $group->map(
                        methods: $params["methods"],
                        pattern: $params["pattern"],
                        callable: $class,
                        name: $params["name"],
                        title: $params["title"] ?? "",
                        permission: $params["permission"] ?? false
                    );
                }
                // manage
                if ($attribute == Manage::class) {
                    print_r($params);
                    $route->get($params["app"])->manage(
                        pattern: $params["pattern"],
                        class: $class,
                        name: $params["name"],
                        title: $params["title"],
                        ways: $params["ways"] ?? [],
                        permission: $params["permission"] ?? false
                    );
                }
                // group
                if ($attribute == Group::class) {
                    $group = $route->get($params["app"])->group($params["pattern"], $params["title"], ...($params["middleware"] ?? []));
                    $groupClass[$class] = $group;
                }
            }
        }

    }

}