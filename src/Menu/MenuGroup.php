<?php

namespace Dux\Menu;

class MenuGroup {

    private string $name;
    private int $order;
    private array $data;

    public function __construct(string $name, int $order = 0) {
        $this->name = $name;
        $this->order = $order;
    }

    public function item(string $name, string $url, int $order): MenuItem {
        $app = new MenuItem($name, $url, $order);
        $this->data[] = $app;
        return $app;
    }

    public function get(): array {
        $items = [];
        foreach ($this->data as $vo) {
            $items[] = $vo->get();
        }
        return [
            "name" => $this->name,
            "order" => $this->order,
            "children" => $items,
        ];
    }
}