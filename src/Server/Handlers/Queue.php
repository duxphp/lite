<?php

namespace Dux\Server\Handlers;

use Dux\App;
use Dux\Queue\QueueProcessor;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\ExitStatusExtension;
use Enqueue\Consumption\Extension\SignalExtension;
use Enqueue\Consumption\QueueConsumer;
use Workerman\Worker;

class Queue
{

    static function start(): void
    {
        $group = App::config('queue')->get('group', 'default');
        $processes = App::config('queue')->get('processes', 1);

        $worker = new Worker();
        $worker->name = 'queue';
        $worker->count = $processes;

        $exitStatusExtension = new ExitStatusExtension();
        $worker->onWorkerStart = function () use ($group, $exitStatusExtension) {
            $retry = (int)App::config("queue")->get("retry", 3);
            $context = App::queue()->context;
            $queueConsumer = new QueueConsumer($context, new ChainExtension([
                new SignalExtension(),
                $exitStatusExtension
            ]), [], null, 1000);
            $queueConsumer->bind($group, new QueueProcessor($context->createQueue($group), $retry));
            $queueConsumer->consume();
        };

        $worker->onWorkerExit = function () use ($exitStatusExtension) {
            exit($exitStatusExtension->getExitStatus());
        };

    }

}