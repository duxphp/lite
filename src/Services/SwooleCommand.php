<?php

namespace Dux\Services;

use Chubbyphp\SwooleRequestHandler\PsrRequestFactory;
use Chubbyphp\SwooleRequestHandler\SwooleResponseEmitter;
use Co\Http\Server;
use Dux\App;
use Dux\Coroutine\ContextManage;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UploadedFileFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Swoole\Coroutine\run;


ini_set('memory_limit', '1G');

class SwooleCommand extends Command
{

    protected static $defaultName = 'swoole';
    protected static $defaultDescription = 'start async web service';


    public function execute(InputInterface $input, OutputInterface $output): int
    {

        run(function () {

            ContextManage::init();

            $http = new Server('0.0.0.0', 8080, false);

            $http->handle('/', function ($request, $response) {


                $factory = new PsrRequestFactory(
                    new ServerRequestFactory(),
                    new StreamFactory(),
                    new UploadedFileFactory()
                );

                (new SwooleResponseEmitter())->emit(
                    App::app()->handle($factory->create($request)),
                    $response
                );


                return $response;

            });


            $http->start();
        });
        return Command::SUCCESS;
    }
}
