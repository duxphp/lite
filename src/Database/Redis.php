<?php
declare(strict_types=1);

namespace Dux\Database;

use Dux\App;
use Dux\Server\ServerEnum;

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
        $database = $this->config["database"] ?: 0;
        $this->drive->select($database);
        if (App::$server === ServerEnum::WORKERMAN) {
            \Workerman\Timer::add(55, function () {
                $this->drive->get('ping');
            });
        }
        return $this->drive;
    }

}