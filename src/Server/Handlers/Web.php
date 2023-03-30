<?php

namespace Dux\Server\Handlers;

use Chubbyphp\WorkermanRequestHandler\PsrRequestFactory;
use Chubbyphp\WorkermanRequestHandler\WorkermanResponseEmitter;
use Dux\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UploadedFileFactory;
use Workerman\Connection\TcpConnection as WorkermanTcpConnection;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Worker;

class Web
{

    static function start(): void
    {
        $worker = new Worker('http://0.0.0.0:8080');
        $worker->name = 'web';
        $worker->count = 10;
        $worker->onMessage = function (WorkermanTcpConnection $workermanTcpConnection, WorkermanRequest $workermanRequest) {
            $request = new PsrRequestFactory(
                new ServerRequestFactory(),
                new StreamFactory(),
                new UploadedFileFactory()
            );

            $workermanTcpConnection->maxRecvPackageSize = 60;
            $workermanTcpConnection->maxSendBufferSize = 60;

            $emit = new WorkermanResponseEmitter();
            $emit->emit(
                App::app()->handle($request->create($workermanTcpConnection, $workermanRequest)),
                $workermanTcpConnection
            );
        };
    }

}