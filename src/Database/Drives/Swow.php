<?php

namespace Dux\Database\Drives;

use Dux\Server\Context\ContextManage;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Swow\Channel;
use Swow\Coroutine;
use Throwable;

class Swow implements DriveInterface
{
    public Manager $capsule;
    private Channel $channel;
    private array $configs;

    public function init(array $configs): void
    {
        $size = 300;
        $this->configs = $configs;
        $this->channel = new Channel($size);

        for ($i = 0; $i < $size; $i++) {
            $this->channel->push($this->createConnection());
        }
    }

    public function get(): Capsule
    {
        $ctx = ContextManage::context();
        if (!$ctx->hasData("db")) {
            $ctx->setData("db", $this->getConnection());
        }
        return $ctx->getData("db");
    }

    public function release()
    {
        $ctx = ContextManage::context();
        $this->releaseConnection($ctx->getData("db"));
    }

    public function createConnection(): Capsule
    {
        $capsule = new Manager;
        foreach ($this->configs as $key => $config) {
            $capsule->addConnection($config, $key);
        }
        $capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Coroutine::run(static function () use ($capsule) {
            while (true) {
                foreach ($capsule->getDatabaseManager()->getConnections() as $connection) {
                    if ($connection->getConfig('driver') === 'mysql') {
                        try {
                            $connection->select('select 1');
                        } catch (Throwable $e) {
                        }
                    }
                }
                sleep(50);
            }
        });
        return $capsule;
    }

    public function getConnection(): Capsule
    {
        return $this->channel->pop();
    }


    public function releaseConnection(Capsule $connection): void
    {
        $this->channel->push($connection);
    }
}