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

}