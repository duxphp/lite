<?php

namespace Dux\Services\Handlers;

use Closure;
use Dux\App;
use Dux\Coroutine\Pool;
use PDO;

class MySqlConnection22 extends \Illuminate\Database\MySqlConnection
{

    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        dump('mysql conn', $config);
        DbPool::getInstance()->start($config);

        if (!App::di()->has('mysql.pool')) {

            $pool = new Pool(function () use ($config) {
                extract($config, EXTR_SKIP);
            });


        }


        $pdo = DbPool::getInstance()->get();
        parent::__construct($pdo, $database, $tablePrefix, $config);
    }

    public function getPdo(): PDO|Closure
    {
        return DbPool::getInstance()->get();
    }

    public function getRawPdo(): PDO|Closure|null
    {
        return DbPool::getInstance()->get();
    }

    public function disconnect(): void
    {
        DbPool::getInstance()->release();
    }

    public function reconnect(): void
    {
        DbPool::getInstance()->reconnect();
    }


}