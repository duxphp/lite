<?php

namespace Dux\React;

use Amp\ByteStream;
use Amp\Http\HttpStatus;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\SocketHttpServer;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket\ResourceServerSocketFactory;
use Dux\App;
use Monolog\Processor\PsrLogMessageProcessor;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Stream;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

ini_set('memory_limit', '1024M');

class ServerCommand extends Command
{

    protected static $defaultName = 'react';
    protected static $defaultDescription = 'start react service';

    public function execute(InputInterface $input, OutputInterface $output): int
    {

        $http = new \React\Http\HttpServer(function (\Psr\Http\Message\ServerRequestInterface $request) {
            return App::app()->handle($request);
        });

        $socket = new \React\Socket\SocketServer('0.0.0.0:8080');
        $http->listen($socket);

        return Command::SUCCESS;
    }
}