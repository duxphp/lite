<?php

namespace Dux\Server\Handlers;

use Channel\Client;
use Dux\App;
use Workerman\Worker;

class Scheduler
{

    static function start(bool $channel = true): void
    {
        $worker = new Worker();
        $worker->name = 'scheduler';

        $worker->onWorkerStart = function () use ($channel) {
            if ($channel) {
                Client::connect('0.0.0.0', App::config('use')->get('app.port', 8080) + 1);
            }
            App::scheduler()->expand();
        };
    }

}