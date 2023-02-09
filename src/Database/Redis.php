<?php
declare(strict_types=1);

namespace Dux\Database;

class Redis {

    private \Redis $drive;

    public function __construct(public array $config) {
        $this->drive = new \Redis;
    }

    public function connect(): \Redis {
        $this->drive->connect($this->config["host"], $this->config["port"], $this->config["timeout"]);
        if ($this->config["auth"]) {
            $this->drive->auth($this->config["auth"]);
        }
        return $this->drive;
    }

}