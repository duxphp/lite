<?php
declare(strict_types=1);

namespace Dux\Route\Attribute;

use Attribute;

#[Attribute]
class Group {

    public string $app;

    public string $pattern;
    public string $title;
    public array $middleware;

    public function __construct(string $app, string $pattern, string $title, object ...$middleware) {
        $this->app = $app;
        $this->pattern = $pattern;
        $this->title = $title;
        $this->middleware = $middleware;
    }

    public function get(): array {
        return [
            "app" => $this->app,
            "pattern" => $this->pattern,
            "title" => $this->title,
            "middleware" => $this->middleware,
        ];
    }
}