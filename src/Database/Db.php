<?php
declare(strict_types=1);

namespace Dux\Database;

use Medoo\Medoo;

class Db {


    static function init(array $config): MedooExtend {
        $database = new MedooExtend($config);
        return $database;
    }
}