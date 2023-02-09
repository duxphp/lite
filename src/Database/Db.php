<?php
declare(strict_types=1);

namespace Dux\Database;

use Illuminate\Database\Capsule\Manager;

class Db {


    public static function init(array $configs): Manager {
        $capsule = new Manager;
        foreach ($configs as $key => $config) {
            $capsule->addConnection($config, $key);
        }
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        return $capsule;
    }
}