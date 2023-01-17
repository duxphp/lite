<?php
declare(strict_types=1);

namespace Dux\Route;

use Dux\App;
use Dux\Handlers\Exception;
use Dux\Route\Attribute\RouteGroup;
use Dux\Route\Attribute\RouteManage;

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
     * 注解路由注册
     * @return void
     */
    public function run(): void {
        $groupClass = [];
        $attributes = (array) App::di()->get("attributes");
        foreach ($attributes as $attribute => $list) {
            foreach ($list as $vo) {
                if (
                    $attribute != RouteManage::class &&
                    $attribute != RouteGroup::class &&
                    $attribute != \Dux\Route\Attribute\Route::class
                ) {
                    continue;
                }
                $params = $vo["params"];
                $class = $vo["class"];
                // group
                if ($attribute == RouteGroup::class) {
                    $group = $this->get($params["app"])->group($params["pattern"], $params["title"], ...($params["middleware"] ?? []));
                    $groupClass[$class] = $group;
                }
                // manage
                if ($attribute == RouteManage::class) {
                    $this->get($params["app"])->manage(
                        pattern: $params["pattern"],
                        class: $class,
                        name: $params["name"],
                        title: $params["title"],
                        ways: $params["ways"] ?? []
                    );
                }
                // route
                if ($attribute == \Dux\Route\Attribute\Route::class) {
                    if (str_contains($class, ":")) {
                        // method
                        [$className, $methodName] = explode(":", $class, 2);
                        if (!$params["app"] && !isset($groupClass[$className])) {
                            throw new \Exception("class [" . $class . "] route attribute parameter missing \"app\" ");
                        }
                        $group = $params["app"] ? $this->get($params["app"]) : $groupClass[$className];
                    } else {
                        // class
                        if (empty($params["app"])) {
                            throw new \Exception("class [" . $class . "] route attribute parameter missing \"app\" ");
                        }
                        $group = $this->get($params["app"]);
                    }
                    $group->map(
                        methods: $params["methods"],
                        pattern: $params["pattern"],
                        callable: $class,
                        name: $params["name"],
                        title: $params["title"] ?? ""
                    );
                }
            }
        }

    }

}