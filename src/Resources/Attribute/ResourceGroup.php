<?php
declare(strict_types=1);

namespace Dux\Resources\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS|Attribute::IS_REPEATABLE)]
class ResourceGroup {

    /**
     * @param string $app 路由注册名
     * @param string $label 资源标签
     * @param string $route 路由前缀
     * @param string $name 资源名
     * @param array $action 方法
     * @param array $middleware 中间件
     */
    public function __construct(
        string $app,
        string $label,
        string $route,
        string $name = '',
        array  $action = [],
        array $middleware = []
    ) {}
}