<?php

namespace Dux\Menu;

class MenuItem {

    private string $name;
    private string $url;
    private mixed $order;
    private string $auth;

    public function __construct(string $name, string $url, $order = 0) {
        $this->name = $name;
        $this->url = $url;
        $this->order = $order;
    }

    public function auth(string $label): self {
        $this->auth = $label;
        return $this;
    }

    public function get(): array {
        return [
            "name" => $this->name,
            "url" => $this->url,
            "order" => $this->order,
            "auth" => $this->auth
        ];
    }
}