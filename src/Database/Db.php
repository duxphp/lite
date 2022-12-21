<?php
declare(strict_types=1);

namespace Dux\Database;

use Illuminate\Database\Capsule\Manager;
use \Illuminate\Database\Connection;

class Db {


    static function init(string $name, array $config): Connection {
        $capsule = new Manager;
        $capsule->addConnection($config, $name);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        return $capsule->getConnection($name);
    }
}