<?php

namespace Dux\Services\Handlers;

use Closure;
use Dux\App;
use Dux\Coroutine\ContextManage;
use Dux\Coroutine\Pool;
use PDO;
use Swoole\Coroutine;

class MySqlConnection extends \Illuminate\Database\MySqlConnection
{

    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        if (!App::di()->has('mysql.pool')) {
            dump('init');
            extract($config, EXTR_SKIP);
            $poolConfig = $pool ?: [];
            $object = new Pool(static function () use ($host, $port, $database, $username, $password, $options) {
                $dsn = "mysql:host={$host};port={$port};dbname={$database}";

                $conn = new PDO($dsn, $username, $password, $options);
                $conn->setAttribute(PDO::ATTR_TIMEOUT, 60);
                return $conn;
            }, $poolConfig['min_size'] ?: 10, $poolConfig['max_size'] ?: 300, $poolConfig['timeout'] ?: 10);
            $object->start();

            App::di()->set('mysql.pool', $object);


        }
        parent::__construct($this->getPoolPdo(), $database, $tablePrefix, $config);
    }

    public function getPdo(): PDO|Closure
    {

        return $this->getPoolPdo();
    }

    public function getRawPdo(): PDO|Closure|null
    {
        return $this->getPoolPdo();
    }

    public function reconnect()
    {
        $pdo = parent::reconnect();

        dump('reconnect', $pdo);
        ContextManage::context()->setValue('mysql.pool', $pdo);
        return $pdo;
    }

    private function getPoolPdo()
    {
        if (!ContextManage::context()->hasValue('mysql.pool')) {
            $conn = App::di()->get('mysql.pool')->get();
            ContextManage::context()->setValue('mysql.pool', $conn);

            Coroutine::defer(function () {
                $conn = ContextManage::context()->getValue('mysql.pool');
                if ($conn) {
                    App::di()->get('mysql.pool')->release($conn);
                }
            });
        }
        return ContextManage::context()->getValue('mysql.pool');
    }

}