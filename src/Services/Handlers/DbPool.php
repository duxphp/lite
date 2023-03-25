<?php

namespace Dux\Services\Handlers;


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


    public function start(int $size, string $dsn, ?string $username, ?string $password, ?array $options): void
    {
        dump('start pool');
        $this->pool = $pdo = new Channel($size);

        $this->config = [
            'dsn' => $dsn,
            'username' => $username,
            'password' => $password,
            'options' => $options
        ];

        for ($i = 0; $i < $size; $i++) {

            //Coroutine::run(static function () use ($pdo, $dsn, $username, $password, $options) {
            $conn = new PDO($dsn, $username, $password, $options);
            //$conn->setAttribute(PDO::ATTR_TIMEOUT, 160);
            $pdo->push($conn);
//                Coroutine::run(static function () use ($conn) {
//
//                    $conn->getAttribute(PDO::ATTR_SERVER_INFO);
//
//                    sleep(50);
//                });
            //});

        }
    }

    public function get(): PDO
    {
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
            dump('release' . Coroutine::getCurrent()->getId());
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
        // 重新创建 PDO 实例并替换当前实例
        return new PDO($this->config['dsn'], $this->config['username'], $this->config['password'], $this->config['options']);

    }

}