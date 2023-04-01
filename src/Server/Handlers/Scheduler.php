<?php

namespace Dux\Server\Handlers;

use Dux\App;
use Workerman\Worker;

class Scheduler
{

    static function start(): void
    {
        $worker = new Worker();
        $worker->name = 'scheduler';

        $worker->onWorkerStart = function () {
            App::scheduler()->expand();
        };
    }

}