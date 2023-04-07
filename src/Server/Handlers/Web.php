<?php

namespace Dux\Server\Handlers;

use Channel\Client;
use Chubbyphp\WorkermanRequestHandler\PsrRequestFactory;
use Dux\App;
use Dux\Server\Interceptor\StaticFile;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UploadedFileFactory;
use Workerman\Connection\TcpConnection as WorkermanTcpConnection;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Protocols\Http\Response;
use Workerman\Timer;
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

        $worker->onWorkerStart = function () {
            Client::connect('0.0.0.0', App::config('use')->get('app.port', 8080) + 1);

            Client::on('notify', function ($data) {
                global $notify;
                if ($data['topic'] != $notify['topic']) {
                    return;
                }
                $response = send($notify['response'], 'ok', (array)$data['data']);
                $notify['conn']->close(Web::transition($response));
            });
        };

        $worker->onMessage = static function (WorkermanTcpConnection $workermanTcpConnection, WorkermanRequest $workermanRequest) {

            // 静态文件
            if (StaticFile::run($workermanTcpConnection, $workermanRequest)) {
                return;
            }

            // 路由处理
            $request = new PsrRequestFactory(
                new ServerRequestFactory(),
                new StreamFactory(),
                new UploadedFileFactory()
            );
            $request = $request->create($workermanTcpConnection, $workermanRequest);
            App::di()->get('error')->forceContentType(null);
            $response = App::app()->handle($request);

            // 通知处理
            $topic = $response->getHeaderLine('notify');
            if ($topic) {
                global $notify;
                $notify = [
                    'topic' => $topic,
                    'response' => $response,
                    'conn' => $workermanTcpConnection,
                ];
                Timer::add(30, function () use ($workermanTcpConnection, $response) {
                    $workermanTcpConnection->close(Web::transition($response));
                });
                return;
            }

            $workermanTcpConnection->send(Web::transition($response));

        };

        $worker->onClose = function () {
            global $notify;
            unset($notify);
        };
    }

    public static function transition(ResponseInterface $response): Response
    {
        return (new Response())
            ->withStatus($response->getStatusCode(), $response->getReasonPhrase())
            ->withHeaders($response->getHeaders())
            ->withBody((string)$response->getBody());
    }

}