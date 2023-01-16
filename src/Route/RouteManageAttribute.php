<?php
declare(strict_types=1);

namespace Dux\Route;

use Attribute;

#[Attribute]
class RouteManageAttribute {

    public function __construct(
        public string $app,
        public string $pattern,
        public string $class,
        public string $name,
        public string $title = "",
        public array $ways = [],
        public bool   $permission = true) {
    }

    public function get(): array {
        return [
            "app" => $this->app,
            "pattern" => $this->pattern,
            "class" => $this->class,
            "name" => $this->name,
            "title" => $this->title,
            "ways" => $this->ways,
            "permission" => $this->permission,
        ];
    }
}