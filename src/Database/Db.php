<?php
declare(strict_types=1);

namespace Dux\Database;

use Dux\Services\Handlers\DbMysql;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Events\Dispatcher;

class Db
{

    public static function init(array $configs): Manager
    {
        $capsule = new Manager;

        Connection::resolverFor('async_mysql', function ($connection, $database, $prefix, $config) {
            $connector = new DbMysql();
            $pdoConnection = $connector->connect($config);
            return new MySqlConnection($pdoConnection, $database, $prefix, $config);
        });

        foreach ($configs as $key => $config) {
            $capsule->addConnection($config, $key);
        }
        $event = new Dispatcher(new Container);
        $capsule->setEventDispatcher($event);
        $capsule->bootEloquent();


//        $source = new EloquentDataSource(
//            $capsule->getDatabaseManager(),
//            $event,
//        );
//        clock()->addDataSource($source);
//
//        $source->listenToEvents();
        return $capsule;
    }
}