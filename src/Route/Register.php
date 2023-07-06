<?php
declare(strict_types=1);

namespace Dux\Route;

use Dux\App;
use Dux\Bootstrap;
use Dux\Handlers\Exception;
use Dux\Route\Attribute\RouteGroup;
use Dux\Route\Attribute\RouteManage;

class Register
{

    public array $app = [];
    public array $path = [];

    /**
     * 设置路由应用
     * @param string $name
     * @param Route $route
     * @return void
     */
    public function set(string $name, Route $route): void
    {
        $this->app[$name] = $route;
    }

    /**
     * 获取路由应用
     * @param string $name
     * @return Route
     */
    public function get(string $name): Route
    {

        if (!isset($this->app[$name])) {
            throw new Exception("The routing app [$name] is not registered");
        }
        return $this->app[$name];
    }

    /**
     * 注解路由注册
     * @return void
     */
    public function registerAttribute(Bootstrap $bootstrap): void
    {
        $attributes = (array)App::di()->get("attributes");

        $permission = $bootstrap->getPermission();
        $groupClass = [];
        $permissionClass = [];

        foreach ($attributes as $attribute => $list) {
            if (
                $attribute != RouteManage::class &&
                $attribute != RouteGroup::class
            ) {
                continue;
            }
            foreach ($list as $vo) {
                $params = $vo["params"];
                $class = $vo["class"];
                [$className, $methodName, $name] = $this->formatFile($class);
                // group
                if ($attribute == RouteGroup::class) {
                    $group = $this->get($params["app"])->group($params["pattern"], $params["title"], ...($params["middleware"] ?? []));
                    $groupClass[$className] = $group;
                    if ($params['permission']) {
                        $permissionClass[$class] = $permission->get($params['permission'])->group($params["title"], $name);
                    }
                }
                // manage
                if ($attribute == RouteManage::class) {
                    $group = $this->get($params["app"])->manage(
                        pattern: $params["pattern"],
                        class: $class,
                        name: $params["name"] ?: $name,
                        title: $params["title"],
                        ways: $params["ways"] ?? [],
                        middleware: $params["middleware"] ?? []
                    );
                    $groupClass[$className] = $group;
                    if ($params['permission']) {
                        $permissionClass[$class] = $permission->get($params['permission'])->manage($params["title"], $name, 0, $params["ways"] ?? []);
                    }
                }
            }
        }

        foreach ($attributes as $attribute => $list) {
            if (
                $attribute != \Dux\Route\Attribute\Route::class
            ) {
                continue;
            }
            foreach ($list as $vo) {
                $params = $vo["params"];
                $class = $vo["class"];
                [$className, $methodName, $name] = $this->formatFile($class);
                // route
                if (str_contains($class, ":")) {
                    // method
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
                $name = $params["name"] ?: $name . ($methodName ? "." . lcfirst($methodName) : "");
                $group->map(
                    methods: is_array($params["methods"]) ? $params["methods"] : [$params["methods"]],
                    pattern: $params["pattern"] ?: '',
                    callable: $class,
                    name: $name,
                    title: $group->title . $params["title"]
                );

                // 权限处理
                if ($permissionClass[$className]) {
                    $permissionClass[$className]->addLabel($name, $params["title"]);
                }

            }
        }
    }

    private function formatFile($class): array
    {
        [$className, $methodName] = explode(":", $class, 2);
        $classArr = explode("\\", $className);
        $layout = array_slice($classArr, -3, 1)[0];
        $name = lcfirst($layout) . "." . lcfirst(end($classArr));

        return [$className, $methodName, $name];
    }

}