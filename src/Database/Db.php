<?php
declare(strict_types=1);

namespace Dux\Database;

use Illuminate\Database\Capsule\Manager;

class Db {

    static function init(array $config): Manager {
        $capsule = new Manager;
        $capsule->addConnection($config);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        return $capsule;
    }

}