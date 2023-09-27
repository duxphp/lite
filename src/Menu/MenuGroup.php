<?php
declare(strict_types=1);

namespace Dux\Menu;

class MenuGroup {

    private array $data = [];
    public function __construct(public string $appName, public string $name, public string $icon = '', public int $sort = 0, public string $prefix = '') {
    }

    public function item(string $name, string $route, string $icon = '', int $sort = 0): MenuItem {
        $app = new MenuItem($this->name, $name, $route, $icon, $sort, $this->prefix);
        $this->data[] = $app;
        return $app;
    }

    public function get(): array {
        $items = [];
        foreach ($this->data as $vo) {
            $items[] = $vo->get();
        }
        return [
            "key" => $this->appName . '/' . $this->name,
            "name" => $this->name,
            "label" => __($this->name . '.name', 'manage'),
            "sort" => $this->sort,
            "icon" => $this->icon,
            "children" => $items,
        ];
    }
}