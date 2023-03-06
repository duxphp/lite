<?php
declare(strict_types=1);

namespace Dux\Route\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RouteManage {

    public function __construct(
        string $app,
        string $title,
        string $pattern,
        string $name = '',
        array  $ways = []) {}
}