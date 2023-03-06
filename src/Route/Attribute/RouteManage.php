<?php
declare(strict_types=1);

namespace Dux\Route\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RouteManage {

    /**
     * @param string $app 路由注册名
     * @param string $title 标题前缀
     * @param string $pattern 路由前缀
     * @param string $name 路由名
     * @param array $ways 允许方法
     * @param string $permission 权限注册名
     */
    public function __construct(
        string $app,
        string $title,
        string $pattern,
        string $name = '',
        array  $ways = [],
        string $permission = '') {}
}