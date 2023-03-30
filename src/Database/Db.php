<?php
declare(strict_types=1);

namespace Dux\Database;

use Dux\Coroutine\ContextManage;
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

        // 全局实例化

        Connection::resolverFor('async_mysql', static function ($pdo, $database, $prefix, $config) {


            return new MySqlConnection(function () {
                return ContextManage::context()->getValue('mysql.pool');
            }, $database, $prefix, $config);
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