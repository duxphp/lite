<?php

namespace Dux\Server;

use Symfony\Contracts\EventDispatcher\Event;

class ServerEvent extends Event
{
    public function __construct(public ServerEnum $server)
    {
    }

}