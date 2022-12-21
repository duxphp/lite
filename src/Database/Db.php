<?php
declare(strict_types=1);

namespace Dux\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

class Db {

    static function init(array $config): Capsule {
        $capsule = new Capsule;
        $capsule->addConnection($config);
        $capsule->bootEloquent();
        return $capsule;
    }
}