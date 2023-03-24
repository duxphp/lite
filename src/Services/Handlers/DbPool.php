<?php

namespace Dux\Services\Handlers;


use PDO;
use Swow\Channel;
use Swow\Coroutine;

class DbPool
{
    private static DbPool $instances;


    protected array $connections = [];
    private Channel $pool;

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
        $this->pool = new Channel($size);
        for ($i = 0; $i < $size; $i++) {
            $conn = new PDO($dsn, $username, $password, $options);
            $this->pool->push($conn);
            Coroutine::run(static function () use ($conn) {

                $conn->query('select 1')->fetchAll();

                sleep(50);
            });
        }
    }

    public function get(): PDO
    {
        $conn = Coroutine::getCurrent()->dbPool;
        if (!$conn) {
            dump(Coroutine::getCurrent()->getId());
            $conn = $this->pool->pop();
            Coroutine::getCurrent()->dbPool = $conn;
        }
        return $conn;
    }

    public function release(): void
    {
        $conn = Coroutine::getCurrent()->dbPool;
        if ($conn) {
            $this->pool->push($conn);
        }
    }

}