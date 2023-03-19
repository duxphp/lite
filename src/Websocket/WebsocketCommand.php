<?php

namespace Dux\Websocket;

use Dux\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Worker;


class WebsocketCommand extends Command
{

    protected static $defaultName = 'websocket';
    protected static $defaultDescription = 'start websocket service';

    protected function configure(): void
    {
        $this->setName('websocket')
            ->addArgument(
                'args',
                InputArgument::IS_ARRAY
            )->addOption(
                'daemon',
                'd',
                InputOption::VALUE_OPTIONAL
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $console = $output;
        App::di()->set('ws.console', $console);

        // 初始化websocket
        $port = App::config('service')->get('websocket.port', 1510);
        $worker = new Worker("websocket://0.0.0.0:$port");
        App::di()->set('ws.worker', $worker);

        $handler = new Websocket();

        $worker->onWorkerStart = [$handler, 'onWorkerStart'];
        $worker->onConnect = [$handler, 'onConnect'];
        $worker->onMessage = [$handler, "onMessage"];
        $worker->onClose = [$handler, "onClose"];

        Worker::runAll();
        return Command::SUCCESS;
    }
}
