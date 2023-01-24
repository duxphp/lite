<?php
declare(strict_types=1);

namespace Dux\Permission;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteCollectorProxy;

class Permission {

    private array $data = [];
    private string $pattern = "";

    public function __construct(string $pattern = "") {
        $this->pattern = $pattern;
    }

    public function group(string $name, string $label, int $order = 0): PermissionGroup {
        $group = new PermissionGroup($name, $label, $order, $this->pattern);
        $this->data[] = $group;
        return $group;
    }

    public function manage(string $name, string $label, int $order = 0, array $ways = []): PermissionGroup {
        $group = $this->group($name, $label, $order);
        if (!$ways || in_array("list", $ways)) {
            $group->add("list", "列表");
        }
        if (!$ways || in_array("info", $ways)) {
            $group->add("info", "信息");
        }
        if (!$ways || in_array("add", $ways)) {
            $group->add("add", "添加");
        }
        if (!$ways || in_array("edit", $ways)) {
            $group->add("edit", "编辑");
        }
        if (!$ways || in_array("store", $ways)) {
            $group->add("store", "存储");
        }
        if (!$ways || in_array("del", $ways)) {
            $group->add("del", "删除");
        }
        return $group;
    }

    public function manageSoftDelete(PermissionGroup $group): void {
        $group->add("restore", '恢复');
        $group->add("trashed", '清除');
    }

    public function get(): array {
        $data = [];
        foreach ($this->data as $vo) {
            $data[] = $vo->get();
        }
        return collect($data)->sortBy("order")->toArray();
    }

    public function getData(): array {
        $data = [];
        foreach ($this->data as $vo) {
            $data = [...$data, ...$vo->getData()];
        }
        return $data;
    }

}