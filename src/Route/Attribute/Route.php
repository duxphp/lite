<?php
declare(strict_types=1);

namespace Dux\Route\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Route {

    public function __construct(
        array|string  $methods,
        string $title,
        string $app = "",
        string $pattern = "",
        string $name = "") {
    }
}