<?php
declare(strict_types=1);

namespace Dux\Menu;


class MenuApp
{

    private array $data = [];

    public function __construct(public string $name, public array $config = [], public string $prefix = '')
    {
    }

    public function group(string $name, $icon = '', int $sort = 0): MenuGroup
    {
        $app = new MenuGroup($this->name, $name, $icon, $sort, $this->prefix);
        $this->data[] = $app;
        return $app;
    }

    public function item(string $name, string $route, string $icon = '', int $sort = 0): MenuItem
    {
        $app = new MenuItem($this->name, $name, $route, $icon, $sort, $this->prefix);
        $this->data[] = $app;
        return $app;
    }

    public function get(): array
    {
        $data = [];
        foreach ($this->data as $vo) {
            $data[] = $vo->get();
        }
        return [
            ...$this->config,
            ...[
                "name" => $this->name,
                "key" => '/' . $this->name,
                "label" => __($this->name . '.name', 'manage'),
                "route" => ($this->config["route"] ? $this->prefix . '/' : '') . $this->config["route"],
                "sort" => $this->config["sort"] ?: 0,
                "children" => $data,
            ]
        ];
    }
}