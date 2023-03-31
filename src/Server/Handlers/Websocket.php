<?php

namespace Dux\Server\Handlers;

use Dux\App;
use Workerman\Worker;

class Websocket
{

    static function start(): void
    {
        $port = App::config('use')->get('app.port', 8080);
        $port = $port + 1;
        $worker = new Worker("websocket://0.0.0.0:$port");
        $worker->name = 'websocket';
        App::di()->set('ws.worker', $worker);

        $handler = new \Dux\Websocket\Websocket();

        $worker->onWorkerStart = [$handler, 'onWorkerStart'];
        $worker->onConnect = [$handler, 'onConnect'];
        $worker->onMessage = [$handler, "onMessage"];
        $worker->onClose = [$handler, "onClose"];
    }

}