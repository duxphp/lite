<?php

namespace Dux\Amp;

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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Amp\trapSignal;
use const SIGHUP;
use const SIGINT;
use const SIGQUIT;
use const SIGTERM;

ini_set('memory_limit', '1024M');

class ServerCommand extends Command
{

    protected static $defaultName = 'amp';
    protected static $defaultDescription = 'start amp service';

    public function execute(InputInterface $input, OutputInterface $output): int
    {

        $logHandler = new StreamHandler(ByteStream\getStdout());
        $logHandler->pushProcessor(new PsrLogMessageProcessor());
        $logHandler->setFormatter(new ConsoleFormatter);
        App::log('server')->pushHandler($logHandler);
        App::log('server')->useLoggingLoopDetection(false);

        $server = new SocketHttpServer(
            App::log('server'),
            new ResourceServerSocketFactory(),
            new SocketClientFactory(App::log('server')),
        );

        $server->expose("0.0.0.0:1338");
        $server->expose("[::]:1338");

        $server->start(new ClosureRequestHandler(function (Request $request): Response {

            App::app()->handle(new \Slim\Psr7\Request(
                $request->getMethod(),
                $request->getUri(),
                $request->getHeaders(),
                $request->getCookies(),
                $request->ser(),
            ));
            return new Response(
                status: HttpStatus::OK,
                headers: ["content-type" => "text/plain; charset=utf-8"],
                body: "Hello, World!",
            );

            static $counter = 0;

            // We can keep state between requests, but if you're using multiple server processes,
            // such state will be separate per process.
            // Note: You might see the counter increase by more than one per reload, because browser
            // might try to load a favicon.ico or similar.
            return new Response(
                status: HttpStatus::OK,
                headers: ["content-type" => "text/plain; charset=utf-8",],
                body: "You're visitor #" . (++$counter) . "."
            );
        }), new DefaultErrorHandler());


// Await a termination signal to be received.
        $signal = trapSignal([SIGHUP, SIGINT, SIGQUIT, SIGTERM]);

        App::log('server')->info(sprintf("Received signal %d, stopping HTTP server", $signal));

        $server->stop();

        return Command::SUCCESS;
    }
}