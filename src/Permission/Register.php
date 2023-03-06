<?php
declare(strict_types=1);

namespace Dux\Permission;

use DI\DependencyException;
use DI\NotFoundException;
use Dux\App;
use Dux\Handlers\Exception;
use \Dux\Permission\Attribute\PermissionGroup;
use \Dux\Permission\Attribute\PermissionManage;
use \Dux\Permission\Attribute\Permission as PermissionAttr;

class Register {

    public array $app = [];

    /**
     * 设置权限应用
     * @param string $name
     * @param Permission $permission
     * @return void
     */
    public function set(string $name, Permission $permission): void {
        $this->app[$name] = $permission;
    }

    /**
     * 获取路由应用
     * @param string $name
     * @return Permission
     */
    public function get(string $name): Permission {

        if (!isset($this->app[$name])) {
            throw new Exception("The menu permission [$name] is not registered");
        }
        return $this->app[$name];
    }

    /**
     * 注解权限注册
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function registerAttribute(): void {
        $groupClass = [];
        $attributes = (array)App::di()->get("attributes");
        foreach ($attributes as $attribute => $list) {
            if (
                $attribute != PermissionGroup::class &&
                $attribute != PermissionManage::class &&
                $attribute != PermissionAttr::class
            ) {
                continue;
            }
            foreach ($list as $vo) {
                $params = $vo["params"];
                $class = $vo["class"];
                $classArr = explode("\\", $class);
                $layout = array_slice($classArr, -3, 1)[0];
                $label = lcfirst($layout) . "." . lcfirst(end($classArr));
                // group
                if ($attribute == PermissionGroup::class) {
                    $group = $this->get($params["app"])->group(
                        name: $params["name"],
                        label: $params["label"] ?: $label,
                        order: $params["order"] ?: 0
                    );
                    $groupClass[$class] = $group;
                }
                // manage
                if ($attribute == PermissionManage::class) {
                    $group = $this->get($params["app"])->manage(
                        name: $params["name"],
                        label: $params["label"] ?: $label,
                        order: $params["order"] ?: 0,
                        ways: $params["ways"] ?: []
                    );
                    $groupClass[$class] = $group;
                }
                // item
                if ($attribute == PermissionAttr::class) {
                    [$className, $methodName] = explode(":", $class, 2);
                    if (!isset($groupClass[$className])) {
                        throw new \Exception("class [" . $class . "] permission annotations are missing group annotations");
                    }
                    $groupClass[$className]->add(
                        label: $params["label"],
                        name: $params["name"] ?: lcfirst($methodName)
                    );
                }
            }
        }

    }


}