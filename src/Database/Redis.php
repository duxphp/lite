<?php
declare(strict_types=1);

namespace Dux\Database;

class Redis
{

    private \Redis $drive;

    public function __construct(public array $config)
    {
        $this->drive = new \Redis;
    }

    public function connect(): \Redis
    {
        $this->drive->connect($this->config["host"], $this->config["port"], $this->config["timeout"]);
        if ($this->config["auth"]) {
            $this->drive->auth($this->config["auth"]);
        }
        $database = $this->config["database"] ?: 0;
        $this->drive->select($database);
        if ($this->config["optPrefix"]) {
            $this->drive->setOption(\Redis::OPT_PREFIX, $this->config["optPrefix"]);
        }
        return $this->drive;
    }

}