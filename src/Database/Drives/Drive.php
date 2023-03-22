<?php

namespace Dux\Database\Drives;

use Dux\App;
use Dux\Handlers\Exception;
use Dux\Server\ServerEnum;
use Illuminate\Database\Capsule\Manager;

class Drive
{

    public DriveInterface $handler;

    public function __construct(array $config)
    {
        $this->handler = match (App::$server) {
            ServerEnum::FPM => new Fpm(),
            ServerEnum::SWOW => new Swow(),
            default => throw new Exception('Database driver does not exist'),
        };
        $this->handler->init($config);
    }

    public function get(): Manager
    {
        return $this->handler->get();
    }
    
    public function release()
    {
        return $this->handler->release();
    }
}