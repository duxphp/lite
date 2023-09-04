<?php
declare(strict_types=1);

namespace Dux\Permission;

class PermissionGroup {
    private int $order;
    private string $name;
    private string $label;
    private array $data = [];
    private string $pattern;

    public function __construct(string $label, string $name, int $order, string $pattern = "") {
        $this->label = $label;
        $this->name = $name;
        $this->order = $order;
        $this->pattern = $pattern;
    }

    public function add(string $label, string $name): self {
        $this->data[] = [
            "name" => $this->pattern .$this->name . "." . $name,
            "label" => $label,
        ];
        return $this;
    }

    public function get(): array {
        return [
            "label" => $this->label,
            "name" => "group:" . $this->pattern . $this->name,
            "order" => $this->order,
            "children" => $this->data,
        ];
    }

    public function getData(): array {
        $data = [];
        foreach ($this->data as $vo) {
            $data[] = $vo["name"];
        }
        return $data;
    }
}