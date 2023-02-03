<?php
declare(strict_types=1);

namespace Dux\Menu;

class MenuApp {

    private array $config = [];
    private array $data = [];
    private string $pattern;

    public function __construct(array $config = [], string $pattern = "") {
        $this->config = $config;
        $this->pattern = $pattern;
    }

    public function group(string $name, int $order = 0): MenuGroup {
        $app = new MenuGroup($name, $order, $this->pattern);
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
            "url" => $this->config["url"] ? $this->pattern . $this->config["url"] : "",
            "order" => $this->config["order"] ?: 0,
            "children" => $group,
        ];
    }
}