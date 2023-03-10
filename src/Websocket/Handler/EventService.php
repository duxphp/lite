<?php

namespace Dux\Websocket\Handler;

use DI\DependencyException;
use DI\NotFoundException;
use Dux\Websocket\Websocket;
use Dux\App;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Connection\TcpConnection;

class EventService extends \Symfony\Contracts\EventDispatcher\Event
{

    public OutputInterface $console;

    /**
     * @param Websocket $ws
     */
    public function __construct(public Websocket $ws)
    {
    }


}