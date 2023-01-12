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

    public function manage(string $name, string $label, int $order = 0): PermissionGroup {
        $group = $this->group($name, $label, $order);
        $group->add("list", "列表");
        $group->add("info", "详情");
        $group->add("add", "添加");
        $group->add("edit", "编辑");
        $group->add("del", "删除");
        return $group;
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
            $data = [...$data, $vo->getData()];
        }
        return $data;
    }

}