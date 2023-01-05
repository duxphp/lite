<?php

namespace Dux\Menu;

class MenuItem {

    private string $name;
    private string $url;
    private int $order = 0;
    private string $auth = "";
    private string $pattern;

    public function __construct(string $name, string $url, int $order = 0, string $pattern = "") {
        $this->name = $name;
        $this->url = $url;
        $this->order = $order;
        $this->pattern = $pattern;
    }

    public function auth(string $label): self {
        $this->auth = $label;
        return $this;
    }

    public function get(): array {
        return [
            "name" => $this->name,
            "url" => $this->pattern . $this->url,
            "order" => $this->order,
            "auth" => $this->auth
        ];
    }
}