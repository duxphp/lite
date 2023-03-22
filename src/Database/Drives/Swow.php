<?php

namespace Dux\Database\Drives;

use Dux\Server\Context\ContextManage;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;
use PDO;
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
        $size = 30;
        $this->configs = $configs;
        $this->channel = new Channel($size);

        for ($i = 0; $i < $size; $i++) {
            $this->channel->push($this->createConnection());
        }
    }

    public function get(): Manager
    {
        $ctx = ContextManage::context();
        if (!$ctx->hasData("db")) {
            print_r('new Conn');
            $ctx->setData("db", $this->getConnection());
        }
        return $ctx->getData("db");
    }

    public function release()
    {
        $ctx = ContextManage::context();
        if ($ctx->getData("db")) {
            $this->releaseConnection($ctx->getData("db"));
        }
    }

    public function createConnection(): Manager
    {
        $capsule = new Manager;
        foreach ($this->configs as $key => $config) {

            $config['options'] = [
                PDO::ATTR_TIMEOUT => 600,
            ];

            $capsule->addConnection($config, $key);
        }
        $capsule->setEventDispatcher(new Dispatcher(new Container));
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
                sleep(30);
            }
        });
        return $capsule;
    }

    public function getConnection(): Manager
    {
        return $this->channel->pop();
    }


    public function releaseConnection(Manager $connection): void
    {
        print_r('release Conn');
        $this->channel->push($connection);
    }
}