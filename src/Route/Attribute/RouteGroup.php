<?php
declare(strict_types=1);

namespace Dux\Route\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RouteGroup {

    public function __construct(
        public string $app,
        public string $title,
        public string $pattern = "",
        public array $middleware = []) {}


}