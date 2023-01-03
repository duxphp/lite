<?php
declare(strict_types=1);

namespace Dux\Menu;

use Dux\Handlers\Exception;

class Register {

    public array $app = [];

    /**
     * 设置菜单应用
     * @param string $name
     * @param Menu $route
     * @return void
     */
    public function set(string $name, Menu $route): void  {
        $this->app[$name] = $route;
    }

    /**
     * 获取路由应用
     * @param string $name
     * @return Menu
     */
    public function get(string $name): Menu {

        if (!isset($this->app[$name])) {
            throw new Exception("The menu app [$name] is not registered");
        }
        return $this->app[$name];

    }

}