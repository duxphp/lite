<?php

namespace Dux\Permission;

class PermissionGroup {
    private int $order;
    private string $name;
    private array $data;
    private string $pattern;

    public function __construct(string $name, int $order, string $pattern = "") {
        $this->name = $name;
        $this->order = $order;
        $this->pattern = $pattern;
    }

    public function add(string $label, string $name): self {
        $this->data[] = [
            "label" => $label,
            "name" => $name,
        ];
        return $this;
    }

    public function get(): array {
        return [
            "name" => $this->pattern . $this->name,
            "order" => $this->order,
            "children" => $this->data,
        ];
    }
}