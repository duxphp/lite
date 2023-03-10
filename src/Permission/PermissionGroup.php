<?php
declare(strict_types=1);

namespace Dux\Permission;

class PermissionGroup {
    private int $order;
    private string $name;
    private string $label;
    private array $data = [];
    private string $pattern;

    public function __construct(string $name, string $label, int $order, string $pattern = "") {
        $this->name = $name;
        $this->order = $order;
        $this->label = $label;
        $this->pattern = $pattern;
    }

    public function add(string $label, string $name): self {
        $this->data[] = [
            "label" => $this->pattern .$this->label . "." . $label,
            "name" => $name,
        ];
        return $this;
    }



    public function addLabel(string $label, string $name): self {
        $this->data[] = [
            "label" => $label,
            "name" => $name,
        ];
        return $this;
    }


    public function softDelete(): self {
        $this->add("restore", '恢复');
        $this->add("trashed", '清除');
        return $this;
    }

    public function get(): array {
        return [
            "name" => $this->name,
            "label" => "group:" . $this->pattern . $this->label,
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