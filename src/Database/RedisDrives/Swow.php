<?php

namespace Dux\Database\RedisDrives;

use Dux\Server\Context\ContextManage;
use Illuminate\Database\Capsule\Manager;
use Redis;
use Swow\Channel;
use Swow\Coroutine;

class Swow
{


    public Manager $capsule;
    private Channel $channel;
    private array $configs;

    public function init(array $config): void
    {
        $size = 30;
        $this->config = $config;
        $this->channel = new Channel($size);

        for ($i = 0; $i < $size; $i++) {
            $this->channel->push($this->createConnection());
        }
    }

    public function get(): Manager
    {
        $ctx = ContextManage::context();
        if (!$ctx->hasData("redis")) {
            print_r('new Conn redis');
            $ctx->setData("redis", $this->getConnection());
        }
        return $ctx->getData("redis");
    }

    public function release(): void
    {
        $ctx = ContextManage::context();
        if ($ctx->getData("redis")) {
            $this->releaseConnection($ctx->getData("redis"));
        }
    }

    public function createConnection(): Redis
    {
        $redis = new Redis;
        $redis->connect($this->config["host"], $this->config["port"], $this->config["timeout"]);
        if ($this->config["auth"]) {
            $redis->auth($this->config["auth"]);
        }
        $database = $this->config["database"] ?: 0;
        $redis->select($database);
        Coroutine::run(static function () use ($redis) {
            while (true) {
                $redis->get('ping');
                sleep(30);
            }
        });
        return $redis;
    }

    public function getConnection(): Redis
    {
        return $this->channel->pop();
    }
    
    public function releaseConnection(Redis $connection): void
    {
        $this->channel->push($connection);
    }
}