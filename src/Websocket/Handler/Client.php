<?php

namespace Dux\Websocket\Handler;

use Workerman\Connection\TcpConnection;

class Client
{
    public function __construct(public TcpConnection $connection, public string $sub, public int|string $id)
    {
    }

    public function send(string $type, string $message = '', array $data = []): ?bool
    {
        $content = json_encode(['type' => $type, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
        return $this->connection->send($content);
    }


}