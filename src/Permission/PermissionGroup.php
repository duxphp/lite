<?php
declare(strict_types=1);

namespace Dux\Permission;

class PermissionGroup {
    private int $order = 0;
    private string $name = "";
    private string $label = "";
    private array $data = [];
    private string $pattern = "";

    public function __construct(string $name, string $label, int $order, string $pattern = "") {
        $this->name = $name;
        $this->order = $order;
        $this->label = $label;
        $this->pattern = $pattern;
    }

    public function add(string $label, string $name): self {
        $this->data[] = [
            "label" => $this->pattern .".". $this->label . "." . $label,
            "name" => $name,
        ];
        return $this;
    }

    public function get(): array {
        return [
            "name" => $this->name,
            "label" => $this->label,
            "order" => $this->order,
            "children" => $this->data,
        ];
    }

    public function getData(): array {
        $data = [];
        foreach ($this->data as $vo) {
            $data[] = $vo["label"];
        }
        return $data;
    }
}