<?php

namespace Dux\Server\Handlers;

use Dux\App;
use Workerman\Worker;

class Websocket
{

    static function start(): void
    {
        $progress = App::config('use')->get('app.progress', 4);
        $port = App::config('use')->get('app.port', 8080);
        $port = $port + 2;
        $worker = new Worker("websocket://0.0.0.0:$port");
        $worker->name = 'websocket';
        $worker->count = $progress;
        App::di()->set('ws.worker', $worker);

        $handler = new \Dux\Websocket\Websocket();

        $worker->onWorkerStart = [$handler, 'onWorkerStart'];
        $worker->onConnect = [$handler, 'onConnect'];
        $worker->onMessage = [$handler, "onMessage"];
        $worker->onClose = [$handler, "onClose"];
    }

}