<?php

namespace Dux\Websocket;

use Dux\App;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


use Workerman\Timer;
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

        $port = App::config('service')->get('websocket.port', 1510);
        $worker = new Worker("websocket://0.0.0.0:$port");
        App::di()->set('ws.worker', $worker);

        $handler = new Websocket();

        $worker->onWorkerStart = function (Worker $worker) use ($handler, $console) {
            if (Worker::$daemonize) {
                return;
            }

            $lastMtime = time();
            Timer::add(1, function () use (&$lastMtime, $console) {
                $dirIterator = new RecursiveDirectoryIterator(App::$appPath);
                $iterator = new RecursiveIteratorIterator($dirIterator);
                foreach ($iterator as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) != 'php') {
                        continue;
                    }
                    if ($lastMtime < $file->getMTime()) {
                        $console->writeln("<info>file reload：$file</info>");
                        posix_kill(posix_getppid(), SIGUSR1);
                        $lastMtime = $file->getMTime();
                        break;
                    }
                }

            }, [App::$appPath]);
            $handler->onWorkerStart($worker);
        };
        $worker->onConnect = [$handler, 'onConnect'];
        $worker->onMessage = [$handler, "onMessage"];
        $worker->onClose = [$handler, "onClose"];

        Worker::runAll();
        return Command::SUCCESS;
    }
}
