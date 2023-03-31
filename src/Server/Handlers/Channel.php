<?php

namespace Dux\Server\Handlers;

use Channel\Server;
use Dux\App;

class Channel
{

    static function start(): void
    {
        $port = App::config('use')->get('app.port', 8080);
        $port = $port + 1;
        new Server('0.0.0.0', $port);
    }

}