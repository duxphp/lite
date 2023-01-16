<?php
declare(strict_types=1);

namespace Dux\Route;

use Dux\Handlers\Exception;

class Register {

    public array $app = [];
    public array $path = [];

    /**
     * 设置路由应用
     * @param string $name
     * @param Route $route
     * @return void
     */
    public function set(string $name, Route $route): void  {
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
     * 注册路由目录
     * @param string $path
     * @return void
     */
    public function path(string $path): void {
        $this->path[] = $path;
    }

}