<?php
declare(strict_types=1);

namespace Dux\Route\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Route {

    /**
     * @param array|string $methods 请求方法
     * @param string $title 路由标题
     * @param string $app 路由注册名，在 RouteGroup 内可不填
     * @param string $pattern 路由匹配
     * @param string $name 路由名称
     */
    public function __construct(
        array|string  $methods,
        string $title,
        string $app = "",
        string $pattern = "",
        string $name = "") {
    }
}