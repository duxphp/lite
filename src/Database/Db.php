<?php
declare(strict_types=1);

namespace Dux\Database;

use Medoo\Medoo;

class Db {

    static function init(array $config): Medoo {
        $database = new Medoo($config);
        return $database;
    }
}