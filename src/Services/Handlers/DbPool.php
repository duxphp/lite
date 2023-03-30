<?php

namespace Dux\Services\Handlers;


use Exception;
use PDO;
use PDOException;
use Swow\Channel;
use Swow\Coroutine;

class DbPool
{
    private static DbPool $instances;


    protected array $connections = [];
    private Channel $pool;
    private array $config = [];

    public static function getInstance(): DbPool
    {
        if (!isset(self::$instances)) {
            self::$instances = new DbPool();
        }
        return self::$instances;
    }

    private function __construct()
    {
        dump('new pool');
    }

    private function __clone(): void
    {
    }


    public function start(array $config): void
    {

        $size = 20;
        dump('start pool');
        $this->pool = new Channel($size);

        extract($config, EXTR_SKIP);

        $dsn = "mysql:host={$host};port={$port};dbname={$database}";
        $this->config = [
            'dsn' => $dsn,
            'username' => $username,
            'password' => $password,
            'options' => $options
        ];
        //Coroutine::run(static function () use ($pdo, $dsn, $username, $password, $options, $size) {
        for ($i = 0; $i < $size; $i++) {

            try {
                $conn = new PDO($dsn, $username, $password, $options);
                $conn->setAttribute(PDO::ATTR_TIMEOUT, 160);
                $this->pool->push($conn);
            } catch (Exception $e) {
                dump($e);
            }
//                Coroutine::run(static function () use ($conn) {
//
//                    $conn->getAttribute(PDO::ATTR_SERVER_INFO);
//
//                    sleep(50);
//                });
            //});

        }
        //});
        dump('pool ok');
    }

    public function get(): PDO
    {
        dump('get pdo');
        $conn = Coroutine::getCurrent()->dbPool;
        if (!$conn) {
            $conn = $this->pool->pop();
            Coroutine::getCurrent()->dbPool = $conn;
        } else {
            dump('get' . Coroutine::getCurrent()->getId());
        }

        if (!$this->isConnected($conn)) {
            // 重新连接
            Coroutine::getCurrent()->dbPool = $this->reconnect();
        }

        return $conn;
    }

    public function release(): void
    {
        $conn = Coroutine::getCurrent()->dbPool;
        if ($conn) {
            dump('release' . Coroutine::getCurrent()->getId() . ' : ' . $this->pool->getLength());
            $this->pool->push($conn);
        }
    }

    public function isConnected($conn): bool
    {
        try {
            $conn->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function reconnect(): PDO
    {
        Coroutine::getCurrent()->dbPool = new PDO($this->config['dsn'], $this->config['username'], $this->config['password'], $this->config['options']);
        return Coroutine::getCurrent()->dbPool;
    }

}