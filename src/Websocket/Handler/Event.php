<?php

namespace Dux\Websocket\Handler;

use DI\DependencyException;
use DI\NotFoundException;
use Dux\Websocket\Websocket;
use Dux\App;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Connection\TcpConnection;

class Event extends \Symfony\Contracts\EventDispatcher\Event
{

    public OutputInterface $console;

    /**
     * @param Websocket $ws
     * @param TcpConnection $connection
     * @param array $params
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(public Websocket $ws, public TcpConnection $connection, public array $params = [])
    {
        $this->console = App::di()->get('ws.console');
    }


}