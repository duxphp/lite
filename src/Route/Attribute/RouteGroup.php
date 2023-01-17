<?php
declare(strict_types=1);

namespace Dux\Route\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RouteGroup {

    public function __construct(
        string $app,
        string $pattern,
        string $title,
        array $middleware = []) {}
}