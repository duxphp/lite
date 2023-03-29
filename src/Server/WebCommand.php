<?php
declare(strict_types=1);

namespace Dux\Server;

use Chubbyphp\WorkermanRequestHandler\PsrRequestFactory;
use Chubbyphp\WorkermanRequestHandler\WorkermanResponseEmitter;
use Dux\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UploadedFileFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Connection\TcpConnection as WorkermanTcpConnection;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Worker;

class WebCommand extends Command
{

    protected static $defaultName = 'web';
    protected static $defaultDescription = 'web start service';

    protected function configure(): void
    {
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

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $worker = new Worker('http://0.0.0.0:8080');
        $worker->count = 10;

        $worker->onMessage = function (WorkermanTcpConnection $workermanTcpConnection, WorkermanRequest $workermanRequest) {
            $request = new PsrRequestFactory(
                new ServerRequestFactory(),
                new StreamFactory(),
                new UploadedFileFactory()
            );
            $emit = new WorkermanResponseEmitter();
            $emit->emit(
                App::app()->handle($request->create($workermanTcpConnection, $workermanRequest)),
                $workermanTcpConnection
            );
        };

        Worker::runAll();

        return Command::SUCCESS;
    }
}