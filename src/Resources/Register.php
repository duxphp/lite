<?php
declare(strict_types=1);

namespace Dux\Resources;

use Dux\App;
use Dux\Bootstrap;
use Dux\Handlers\Exception;
use Dux\Permission\Permission;
use Dux\Resources\Attribute\ResourceGroup;
use Dux\Resources\Attribute\Resource;
use Dux\Route\Route;

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
                $attribute != ResourceGroup::class
            ) {
                continue;
            }
            foreach ($list as $vo) {
                $params = $vo["params"];
                $class = $vo["class"];
                [$className, $methodName, $name] = $this->formatFile($class);
                $group = $this->get($params["app"])->manage(
                    pattern: $params["pattern"],
                    class: $class,
                    name: $params["name"] ?: $name,
                    title: $params["label"],
                    ways: $params["action"] ?? [],
                    middleware: $params["middleware"] ?? []
                );

                $groupClass[$className] = $group;
                if ($params['name']) {
                    $permission->set($params['name'], new Permission($params['name']));
                    $permissionClass[$class] = $permission->get($params['name'])->manage($params["label"], $name, 0, $params["action"] ?? []);
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