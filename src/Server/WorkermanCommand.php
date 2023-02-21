<?php
declare(strict_types=1);

namespace Dux\Server;

use Dux\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;


use Chubbyphp\WorkermanRequestHandler\OnMessage;
use Chubbyphp\WorkermanRequestHandler\PsrRequestFactory;
use Chubbyphp\WorkermanRequestHandler\WorkermanResponseEmitter;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UploadedFileFactory;
use Workerman\Worker;
use Workerman\Timer;

class WorkermanCommand extends Command {

    protected static $defaultName = 'server:workerman';
    protected static $defaultDescription = 'Use workerman to start the web service';


    protected function configure(): void {
        $this->setName('web')
            ->addArgument(
                'args',
                InputArgument::IS_ARRAY
            )->addOption(
                'daemon',
                'd',
                InputOption::VALUE_OPTIONAL
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int {
        $port = App::config('service')->get('port', 8080);
        $http = new Worker("http://0.0.0.0:$port");
        $http->count = 4;
        $http->onWorkerStart = function () use ($output) {
            $output->writeln('<info>DuxLite Web Service Start</info>');

            // Db Heartbeat
            Timer::add(55, function () {
                foreach (App::db()->getDatabaseManager()->getConnections() as $connection) {
                    if ($connection->getConfig('driver') == 'mysql') {
                        try {
                            $connection->select('select 1');
                        } catch (Throwable $e) {}
                    }
                }
            });
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