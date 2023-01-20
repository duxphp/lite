<?php

namespace Dux\Server;

use Dux\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


use Chubbyphp\WorkermanRequestHandler\OnMessage;
use Chubbyphp\WorkermanRequestHandler\PsrRequestFactory;
use Chubbyphp\WorkermanRequestHandler\WorkermanResponseEmitter;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UploadedFileFactory;
use Workerman\Worker;

class WorkermanCommand extends Command {

    protected static $defaultName = 'server:workerman';
    protected static $defaultDescription = 'Use workerman to start the web service';


    protected function configure(): void {
        $this->addArgument(
            'port',
            InputArgument::OPTIONAL,
            'please enter the route group name'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int {
        $port = $input->getArgument("port") ?: 8080;
        $http = new Worker("http://0.0.0.0:$port");
        $http->onWorkerStart = function () {
            echo 'Workerman http server is started at http://0.0.0.0:8080'.PHP_EOL;
        };
        $http->onMessage = new OnMessage(
            new PsrRequestFactory(
                new ServerRequestFactory(),
                new StreamFactory(),
                new UploadedFileFactory()
            ),
            new WorkermanResponseEmitter(),
            App::app()
        );
        Worker::runAll();
        return Command::SUCCESS;
    }
}