<?php
declare(strict_types=1);

namespace Dux\Route\Attribute;

use Attribute;

#[Attribute]
class Manage {

    public function __construct(
        public string $app,
        public string $pattern,
        public string $name,
        public string $title = "",
        public array $ways = [],
        public bool   $permission = true) {
    }

    public function get(): array {
        return [
            "app" => $this->app,
            "pattern" => $this->pattern,
            "name" => $this->name,
            "title" => $this->title,
            "ways" => $this->ways,
            "permission" => $this->permission,
        ];
    }
}