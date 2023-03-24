<?php
declare(strict_types=1);

namespace Dux\Database;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;

class Db
{
    
    public static function init(array $configs): Manager
    {
        $capsule = new Manager;
        foreach ($configs as $key => $config) {
            $capsule->addConnection($config, $key);
        }
        $capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->bootEloquent();

        return $capsule;
    }
}