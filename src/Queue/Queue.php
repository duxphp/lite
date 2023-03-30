<?php
declare(strict_types=1);

namespace Dux\Queue;

use Redis;
use RuntimeException;

class Queue
{
    public Redis $client;

    public array $config = [];

    public array $group = [];

    public function __construct(string $type, array $config)
    {
        if ($type !== "redis") {
            throw new RuntimeException("Queue type not supported");
        }
        $this->config = $config;
        $this->client = new Redis;
        $this->client->connect($config["host"], $config["port"]);
        if ($config["auth"]) {
            $this->client->auth($config["auth"]);
        }
        $database = $config["database"] ?: 0;
        $this->client->select($database);
    }

    public function add(string $group = "default"): QueueHandlers
    {
        if (isset($this->group[$group])) {
            return $this->group[$group];
        }
        $queue = new QueueHandlers($this->client, $group);
        $this->group[$group] = $queue;
        return $queue;
    }

}