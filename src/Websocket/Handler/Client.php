<?php

namespace Dux\Websocket\Handler;

use Workerman\Connection\TcpConnection;

class Client
{
    public function __construct(public TcpConnection $connection, public string $sub, public int|string $id, public string $platform = 'web')
    {
    }
}