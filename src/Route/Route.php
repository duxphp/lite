<?php
declare(strict_types=1);

namespace Dux\Route;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteCollectorProxy;

class Route {

    public string $pattern = "";
    private array $middleware = [];
    private array $group = [];
    private array $data = [];
    private string $title;

    /**
     * @param string $pattern 匹配
     * @param string $title 标题
     * @param object ...$middleware 中间件
     */
    public function __construct(string $pattern, string $title = "", object ...$middleware) {
        $this->pattern = $pattern;
        $this->title = $title;
        $this->middleware = $middleware;
    }

    /**
     * 分组
     * @param string $pattern
     * @param string $title
     * @param object ...$middleware
     * @return Route
     */
    public function group(string $pattern, string $title, object ...$middleware): Route {
        $group = new Route($pattern, $title, ...$middleware);
        $this->group[] = $group;
        return $group;
    }

    /**
     * get
     * @param string $pattern
     * @param callable|object|string $callable
     * @param string $name
     * @param string $title
     * @return void
     */
    public function get(string $pattern, callable|object|string $callable, string $name, string $title = ""): void {
        $this->map(["GET"], $pattern, $callable, $name, $title);
    }

    /**
     * post
     * @param string $pattern
     * @param callable|object|string $callable
     * @param string $name
     * @param string $title
     * @return void
     */
    public function post(string $pattern, callable|object|string $callable, string $name, string $title = ""): void {
        $this->map(["POST"], $pattern, $callable, $name, $title);
    }

    /**
     * put
     * @param string $pattern
     * @param callable|object|string $callable
     * @param string $name
     * @param string $title
     * @return void
     */
    public function put(string $pattern, callable|object|string $callable, string $name, string $title = ""): void {
        $this->map(["PUT"], $pattern, $callable, $name, $title);
    }

    /**
     * delete
     * @param string $pattern
     * @param callable|object|string $callable
     * @param string $name
     * @param string $title
     * @return void
     */
    public function delete(string $pattern, callable|object|string $callable, string $name, string $title = ""): void {
        $this->map(["DELETE"], $pattern, $callable, $name, $title);
    }

    /**
     * options
     * @param string $pattern
     * @param callable|object|string $callable
     * @param string $name
     * @param string $title
     * @return void
     */
    public function options(string $pattern, callable|object|string $callable, string $name, string $title = ""): void {
        $this->map(["OPTIONS"], $pattern, $callable, $name, $title);
    }

    /**
     * path
     * @param string $pattern
     * @param callable|object|string $callable
     * @param string $name
     * @param string $title
     * @return void
     */
    public function path(string $pattern, callable|object|string $callable, string $name, string $title = ""): void {
        $this->map(["PATH"], $pattern, $callable, $name, $title);
    }

    /**
     * any
     * @param string $pattern
     * @param callable|object|string $callable
     * @param string $name
     * @param string $title
     * @return void
     */
    public function any(string $pattern, callable|object|string $callable, string $name, string $title = ""): void {
        $this->map(["ANY"], $pattern, $callable, $name, $title);
    }

    /**
     * map
     * @param array $methods [GET, POST, PUT, DELETE, OPTIONS, PATH]
     * @param string $pattern
     * @param string|callable $callable function(Request $request, Response $response)
     * @param string $name
     * @param string $title
     * @return void
     */
    public function map(array $methods, string $pattern, $callable, string $name, string $title = ""): void {
        $this->data[] = [
            "methods" => $methods,
            "pattern" => $pattern,
            "callable" => $callable,
            "name" => $name,
            "title" => $title
        ];
    }

    /**
     * 解析树形路由
     * @param string $pattern
     * @return array
     */
    public function parseTree(string $pattern = ""): array {
        $pattern = $pattern ?: $this->pattern;
        $data = [];
        foreach ($this->data as $route) {
            $route["pattern"] = $pattern . $route["pattern"];
            $data[] = [
                "title" => $route["title"],
                "name" => $route["name"],
                "pattern" => $route["pattern"],
                "methods" => $route["methods"]
            ];
        }
        foreach ($this->group as $group) {
            $data[] = $group->parseTree($pattern . $group->pattern);
        }

        return [
            "title" => $this->title,
            "pattern" => $pattern,
            "data" => $data
        ];
    }


    /**
     * 解析路由列表
     * @param string $pattern
     * @return array
     */
    public function parseData(string $pattern = "", array $middleware = []): array {
        $pattern = $pattern ?: $this->pattern;
        foreach ($this->middleware as $vo) {
            $middleware[] = get_class($vo);
        }
        $data = [];
        foreach ($this->data as $route) {
            $route["pattern"] = $pattern . $route["pattern"];
            $data[] = [
                "title" => $route["title"],
                "name" => $route["name"],
                "pattern" => $route["pattern"],
                "methods" => $route["methods"],
                "middleware" => $middleware
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
    public function run(RouteCollectorProxy $route): void {
        $dataList = $this->data;
        $groupList = $this->group;
        $route = $route->group($this->pattern, function (RouteCollectorProxy $group) use ($dataList, $groupList) {
            foreach ($dataList as $item) {
                $group->map($item["methods"], $item["pattern"], $item["callable"])->setName($item["name"]);
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