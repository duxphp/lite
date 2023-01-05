<?php
declare(strict_types=1);

namespace Dux\Menu;

class MenuGroup {

    private string $name;
    private int $order = 0;
    private array $data = [];
    private string $pattern;

    public function __construct(string $name, int $order = 0, string $pattern = "") {
        $this->name = $name;
        $this->order = $order;
        $this->pattern = $pattern;
    }

    public function item(string $name, string $url, int $order): MenuItem {
        $app = new MenuItem($name, $url, $order, $this->pattern);
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