<?php
declare(strict_types=1);

namespace Dux\Resources\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Resource
{
    /**
     * @param array|string $methods 请求方法
     * @param string $label 资源标签
     * @param string $route 路由匹配
     * @param string $name 资源名
     * @param string $app 路由注册名
     */
    public function __construct(
        array|string $methods,
        string       $label,
        string       $route,
        string       $name = '',
        string       $app = '')
    {
    }
}