<?php
declare(strict_types=1);

namespace Dux\server;

use Dux\App;
use Mix\Http\Message\Factory\ResponseFactory;
use Mix\Http\Message\Factory\ServerRequestFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
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

        $worker->onMessage = function (TcpConnection $connection, Request $request) {
            $factory = new ServerRequestFactory();
            $mixRequest = $factory->createServerRequestFromWorkerMan($request);

            $response = App::app()->handle($mixRequest);


//            $connection->send(
//                (new WorkermanResponse())
//                    ->withStatus($response->getStatusCode(), $response->getReasonPhrase())
//                    ->withHeaders($response->getHeaders())
//                    ->withBody((string)$response->getBody())
//            );
//
//
            $responseFactory = new ResponseFactory();
//
            $responseFactory = $responseFactory->createResponseFromWorkerMan($connection);
            $responseFactory->withStatus($response->getStatusCode(), $response->getReasonPhrase())
                ->withBody($response->getBody());
//            foreach ($response->getHeaders() as $key => $header) {
//                $responseFactory->withHeader($key, $header);
//            }
//            dump('s');
            return $responseFactory->send();
        };

        Worker::runAll();

        return Command::SUCCESS;
    }
}