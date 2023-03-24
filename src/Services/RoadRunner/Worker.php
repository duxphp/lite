<?php

use Dux\App;
use Nyholm\Psr7;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\RoadRunner;

include "vendor/autoload.php";

$worker = RoadRunner\Worker::create();
$psrFactory = new Psr7\Factory\Psr17Factory();

$psr7 = new RoadRunner\Http\PSR7Worker($worker, $psrFactory, $psrFactory, $psrFactory);

$rootPath = realpath(__DIR__ . '/../../../../../../');

$app = App::create($rootPath);

while (true) {
    try {
        $request = $psr7->waitRequest();

        if (!($request instanceof ServerRequestInterface)) {
            break;
        }
    } catch (Throwable) {
        $psr7->respond(new Psr7\Response(400));
        continue;
    }

    try {
        $response = $app->web->handle($request);
        $psr7->respond($response);
    } catch (Throwable) {
        $psr7->respond(new Psr7\Response(500, [], 'Something Went Wrong!'));
    }
}