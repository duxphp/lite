<?php
declare(strict_types=1);

namespace Dux\Route\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Route {

    public function __construct(
        public array  $methods,
        public string $pattern,
        public string $name,
        public string $title = "",
        public bool   $permission = true,
        public string $app = "") {
    }

    public function get(): array {
        return [
            "app" => $this->app,
            "methods" => $this->methods,
            "pattern" => $this->pattern,
            "name" => $this->name,
            "title" => $this->title,
            "permission" => $this->permission,
        ];
    }
}