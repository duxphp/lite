<?php

namespace Dux\Server\Interceptor;

use Dux\App;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

class StaticFile
{

    public static function run(TcpConnection $connection, Request $request): bool
    {
        $filePath = App::$publicPath . $request->path();
        if (is_dir($filePath)) {
            $filePath = rtrim($filePath, '/') . '/index.html';
        }
        if (!is_file($filePath)) {
            return false;
        }
        $response = new Response();
        $response->withFile($filePath);
        $connection->send($response);
        return true;
    }

}