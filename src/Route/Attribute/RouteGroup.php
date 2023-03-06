<?php
declare(strict_types=1);

namespace Dux\Route\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RouteGroup {

    /**
     * @param string $app 路由注册名
     * @param string $title 标题前缀
     * @param string $pattern 路由前缀
     * @param array $middleware 中间件
     * @param string $permission 权限注册名
     */
    public function __construct(
        public string $app,
        public string $title,
        public string $pattern,
        public array $middleware = [],
        string $permission = '') {}


}