<?php
declare(strict_types=1);

namespace Dux\Route;

use Attribute;

#[Attribute]
class RouteAttribute {
    public function __construct(
        public string $app,
        public array  $methods,
        public string $pattern,
        public string $name,
        public string $title = "",
        public bool   $permission = true) {
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