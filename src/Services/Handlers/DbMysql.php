<?php

namespace Dux\Services\Handlers;

use Illuminate\Database\Connectors\ConnectorInterface;
use Illuminate\Database\Connectors\MySqlConnector;
use PDO;
use Swow\Coroutine;

class DbMysql extends MySqlConnector implements ConnectorInterface
{
    private static DbPool $pool;

    public function connect(array $config): PDO
    {
        $dsn = $this->getDsn($config);
        $options = $this->getOptions($config);
        [$username, $password] = [
            $config['username'] ?? null, $config['password'] ?? null,
        ];

        if (!isset(self::$pool)) {
            dump('new link');
            self::$pool = DbPool::getInstance();
            self::$pool->start(10, $dsn, $username, $password, $options);
        }
        return parent::connect($config);
    }

    protected function createPdoConnection($dsn, $username, $password, $options): PDO
    {
        dump('get pdo : ' . Coroutine::getCurrent()->getId());
        return DbPool::getInstance()->get();
    }
}