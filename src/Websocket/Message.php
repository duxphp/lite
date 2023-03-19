<?php
declare(strict_types=1);

namespace Dux\Websocket;

use Workerman\RedisQueue\Client;

class Message
{

    public static Client $redisClient;

    public static function connect($config): void
    {
        $dsn = sprintf(
            'redis://%s%s:%d/%d',
            $config['password'] ? $config['password'] . '@' : '',
            $config['host'],
            $config['port'],
            $config['database']
        );
        self::$redisClient = new Client($dsn);
    }


    public static function send(string $clientApp, string $clientId, string $type, string|array|null $message, ?array $data = []): void
    {
        self::$redisClient->send("message.$clientApp.$clientId", [
            'type' => $type,
            'message' => $message,
            'data' => $data
        ]);

    }


}