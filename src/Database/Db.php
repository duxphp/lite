<?php
declare(strict_types=1);

namespace Dux\Database;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class Db {


    public static function init(array $configs): Manager {
        $capsule = new Manager;
        foreach ($configs as $key => $config) {
            $capsule->addConnection($config, $key);
        }
        $capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        return $capsule;
    }
}