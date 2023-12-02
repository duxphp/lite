<?php

namespace Dux\Server\Handlers;

use Dux\App;
use Exception;
use Workerman\RedisQueue\Client;
use Workerman\Worker;

class Queue
{

    static function start(bool $channel = true): void
    {
        $group = App::config('queue')->get('group');
        $processes = App::config('queue')->get('processes', 4);

        $worker = new Worker();
        $worker->name = 'queue';
        $worker->count = $processes;

        $worker->onWorkerStart = function () use ($group, $channel) {
            if ($channel) {
                \Channel\Client::connect('0.0.0.0', App::config('use')->get('app.port', 8080) + 1);
            }

            $config = App::queue()->config;
            $host = $config['host'];
            $port = $config['port'];
            $auth = $config['auth'];
            $dsn = "redis://$host:$port";
            $client = new Client($dsn, [
                'auth' => $auth,
                'max_attempts' => $config['retry'],
                'prefix' => $config['prefix'],
                'db' => $config['database'] ?: 0,
            ]);
            $client->subscribe($group, function (?array $data) {
                if (!$data) {
                    return;
                }
                [$classMethod, $params] = $data;
                [$class, $method] = $classMethod;

                try {
                    $object = new $class;
                    if (!$method) {
                        $object(...$params);
                    } else if (method_exists($object, $method)) {
                        $object->$method(...$params);
                    }
                } catch (Exception $e) {
                    App::log('queue')->error($e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
                    throw $e;
                }
            });
        };
    }

}