<?php

namespace Dux\Menu;

class MenuApp {

    private array $config;
    private array $data;

    public function __construct(array $config = []) {
        $this->config = $config;
    }

    public function group(string $name): MenuGroup {
        $app = new MenuGroup($name);
        $this->data[] = $app;
        return $app;
    }

    public function get(): array {
        $group = [];
        foreach ($this->data as $vo) {
            $group[] = $vo->get();
        }
        return [
            "name" => $this->config["name"],
            "icon" => $this->config["icon"],
            "url" => $this->config["url"],
            "order" => $this->config["order"],
            "children" => $group,
        ];
    }
}