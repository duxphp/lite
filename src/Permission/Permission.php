<?php
declare(strict_types=1);

namespace Dux\Permission;

class Permission
{

    private array $data = [];
    private string $pattern = "";

    public function __construct(string $pattern = "")
    {
        $this->pattern = $pattern;
    }

    public function group(string $label, string $name, int $order = 0): PermissionGroup
    {
        $group = new PermissionGroup($label, $name, $order, $this->pattern);
        $this->data[] = $group;
        return $group;
    }

    public array $actions = ['list', 'show', 'create', 'edit', 'store', 'delete', 'trash', 'restore'];

    public function resources(string $name, int $order = 0, array|false $actions = []): PermissionGroup
    {
        $group = $this->group($label, $name, $order);

        if ($actions === false) {
            return $group;
        }

        if (!$actions) {
            $maps = $this->actions;
        } else {
            $maps = array_intersect($this->actions, $actions);
        }

        foreach ($maps as $vo) {
            $group->add(__('common.resources.' . $vo), $vo);
        }

        return $group;
    }

    public function get(): array
    {
        $data = [];
        foreach ($this->data as $vo) {
            $data[] = $vo->get();
        }
        return collect($data)->sortBy("order")->toArray();
    }

    public function getData(): array
    {
        $data = [];
        foreach ($this->data as $vo) {
            $data = [...$data, ...$vo->getData()];
        }
        return $data;
    }

}