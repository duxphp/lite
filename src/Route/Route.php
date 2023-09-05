<?php
declare(strict_types=1);

namespace Dux\Route;

use Slim\Routing\RouteCollectorProxy;

class Route
{

    private array $middleware = [];
    private array $group = [];
    private array $data = [];
    private string $app = "";

    /**
     * @param string $pattern
     * @param object ...$middleware
     */
    public function __construct(public string $pattern = "", object ...$middleware)
    {
        $this->middleware = $middleware;
    }

    public function setApp(string $app): void
    {
        $this->app = $app;
    }

    /**
     * 分组
     * @param string $pattern
     * @param object ...$middleware
     * @return Route
     */
    public function group(string $pattern, object ...$middleware): Route
    {
        $group = new Route($pattern, ...$middleware);
        $group->setApp($this->app);
        $this->group[] = $group;
        return $group;
    }

    /**
     * get
     * @param string $pattern
     * @param callable|object|string $callable
     * @param string $name
     * @return void
     */
    public function get(string $pattern, callable|object|string $callable, string $name): void
    {
        $this->map(["GET"], $pattern, $callable, $name);
    }

    /**
     * post
     * @param string $pattern
     * @param callable|object|string $callable
     * @param string $name
     * @return void
     */
    public function post(string $pattern, callable|object|string $callable, string $name): void
    {
        $this->map(["POST"], $pattern, $callable, $name);
    }

    /**
     * put
     * @param string $pattern
     * @param callable|object|string $callable
     * @param string $name
     * @return void
     */
    public function put(string $pattern, callable|object|string $callable, string $name): void
    {
        $this->map(["PUT"], $pattern, $callable, $name);
    }

    /**
     * delete
     * @param string $pattern
     * @param callable|object|string $callable
     * @param string $name
     * @return void
     */
    public function delete(string $pattern, callable|object|string $callable, string $name): void
    {
        $this->map(["DELETE"], $pattern, $callable, $name);
    }

    /**
     * options
     * @param string $pattern
     * @param callable|object|string $callable
     * @param string $name
     * @return void
     */
    public function options(string $pattern, callable|object|string $callable, string $name): void
    {
        $this->map(["OPTIONS"], $pattern, $callable, $name);
    }

    /**
     * path
     * @param string $pattern
     * @param callable|object|string $callable
     * @param string $name
     * @return void
     */
    public function path(string $pattern, callable|object|string $callable, string $name): void
    {
        $this->map(["PATH"], $pattern, $callable, $name);
    }

    /**
     * any
     * @param string $pattern
     * @param callable|object|string $callable
     * @param string $name
     * @return void
     */
    public function any(string $pattern, callable|object|string $callable, string $name): void
    {
        $this->map(["ANY"], $pattern, $callable, $name);
    }

    /**
     * @param string $pattern
     * @param string $class
     * @param string $name
     * @param array|false $actions
     * @param bool $softDelete
     * @param array $middleware
     * @return Route
     */
    public function resources(string $pattern, string $class, string $name, array|false $actions = [], bool $softDelete = false, array $middleware = []): Route
    {
        $group = $this->group($pattern, ...$middleware);

        if ($actions === false) {
            return $group;
        }

        if (!$actions || in_array("list", $actions)) {
            $group->get('', "$class:list", "$name.list");
        }
        if (!$actions || in_array("show", $actions)) {
            $group->get("/{id}", "$class:show", "$name.show");
        }
        if (!$actions || in_array("create", $actions)) {
            $group->post("", "$class:create", "$name.create");
        }
        if (!$actions || in_array("edit", $actions)) {
            $group->put("/{id}", "$class:edit", "$name.edit");
        }
        if (!$actions || in_array("store", $actions)) {
            $group->path("/{id}", "$class:store", "$name.store");
        }
        if (!$actions || in_array("delete", $actions)) {
            $group->delete("/{id}", "$class:delete", "$name.delete");
        }
        if ($softDelete && in_array("trash", $actions)) {
            $group->delete("/{id}/trash", "$class:trash", "$name.trash");
        }
        if ($softDelete && in_array("restore", $actions)) {
            $group->put("/{id}/restore", "$class:restore", "$name.restore");
        }
        return $group;
    }

    /**
     * map
     * @param array $methods [GET, POST, PUT, DELETE, OPTIONS, PATH]
     * @param string $pattern
     * @param string|callable $callable function(Request $request, Response $response)
     * @param string $name
     * @param array $middleware
     * @return void
     */
    public function map(array $methods, string $pattern, string|callable $callable, string $name, array $middleware = []): void
    {
        $this->data[] = [
            "methods" => $methods,
            "pattern" => $pattern,
            "callable" => $callable,
            "name" => $name,
            "middleware" => $middleware ?: []
        ];
    }

    /**
     * 解析树形路由
     * @param string $pattern
     * @param array $middleware
     * @return array
     */
    public function parseTree(string $pattern = "", array $middleware = []): array
    {
        $pattern = $pattern ?: $this->pattern;
        foreach ($this->middleware as $vo) {
            $middleware[] = get_class($vo);
        }
        $data = [];
        foreach ($this->data as $route) {
            $route["pattern"] = $pattern . $route["pattern"];
            $data[] = [
                "name" => $route["name"],
                "pattern" => $route["pattern"],
                "methods" => $route["methods"],
                "middleware" => array_filter([...$middleware, $route['middleware']])
            ];
        }
        foreach ($this->group as $group) {
            $data[] = $group->parseTree($pattern . $group->pattern, $middleware);
        }

        return [
            "pattern" => $pattern,
            "data" => $data
        ];
    }


    /**
     * 解析路由列表
     * @param string $pattern
     * @param array $middleware
     * @return array
     */
    public function parseData(string $pattern = "", array $middleware = []): array
    {
        $pattern = $pattern ?: $this->pattern;
        foreach ($this->middleware as $vo) {
            $middleware[] = get_class($vo);
        }
        $data = [];
        foreach ($this->data as $route) {
            $route["pattern"] = $pattern . $route["pattern"];
            $data[] = [
                "name" => $route["name"],
                "pattern" => $route["pattern"],
                "methods" => $route["methods"],
                "middleware" => array_filter([...$middleware, $route['middleware']])
            ];
        }
        foreach ($this->group as $group) {
            $data = [...$data, ...$group->parseData($pattern . $group->pattern, $middleware)];
        }
        return $data;
    }


    /**
     * 运行路由注册
     * @param RouteCollectorProxy $route
     * @return void
     */
    public function run(RouteCollectorProxy $route): void
    {
        $dataList = $this->data;
        $groupList = $this->group;
        $app = $this->app;
        $route = $route->group($this->pattern, function (RouteCollectorProxy $group) use ($dataList, $groupList, $app) {
            foreach ($dataList as $item) {
                $group->map($item["methods"], $item["pattern"], $item["callable"])->setName($item["name"])->setArgument("app", $app);
            }
            foreach ($groupList as $item) {
                $item->run($group);
            }
        });
        foreach ($this->middleware as $middle) {
            $route->add($middle);
        }
    }

}