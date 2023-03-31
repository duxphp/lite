<?php

namespace Dux\Server\Handlers;

use Chubbyphp\WorkermanRequestHandler\PsrRequestFactory;
use Dux\App;
use Exception;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UploadedFileFactory;
use Workerman\Connection\TcpConnection as WorkermanTcpConnection;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

class Web
{

    static function start(): void
    {
        $progress = App::config('use')->get('app.progress', 4);
        $port = App::config('use')->get('app.port', 8080);
        $worker = new Worker("http://0.0.0.0:$port");
        $worker->name = 'web';
        $worker->count = $progress;

        $worker->onMessage = function (WorkermanTcpConnection $workermanTcpConnection, WorkermanRequest $workermanRequest) {

            $filePath = App::$publicPath . $workermanRequest->path();
            if (is_dir($filePath)) {
                $filePath = rtrim($filePath, '/') . '/index.html';
            }
            if (is_file($filePath)) {
                $response = new Response();
                $response->withFile($filePath);
                $workermanTcpConnection->send($response);
                return;
            }

            $request = new PsrRequestFactory(
                new ServerRequestFactory(),
                new StreamFactory(),
                new UploadedFileFactory()
            );

            $workermanTcpConnection->maxRecvPackageSize = 60;
            $workermanTcpConnection->maxSendBufferSize = 60;

            $request = $request->create($workermanTcpConnection, $workermanRequest);
            try {
                $response = App::app()->handle($request);
            } catch (Exception $e) {
                dump($e->getMessage());
                return;
            }

            $workermanTcpConnection->send(
                (new Response())
                    ->withStatus($response->getStatusCode(), $response->getReasonPhrase())
                    ->withHeaders($response->getHeaders())
                    ->withBody((string)$response->getBody())
            );

        };
    }

}