<?php
declare(strict_types=1);

namespace Dux\Database;

use Dux\App;
use Dux\Database\Drives\DriveInterface;
use Dux\Database\Drives\Fpm;
use Dux\Handlers\Exception;
use Dux\Server\ServerEnum;
use Illuminate\Database\Capsule\Manager;

class Db
{

    public DriveInterface $handler;

    public function __construct(array $config)
    {
        if (App::$server === ServerEnum::FPM) {
            $this->handler = new Fpm();
        }

        $this->handler = match (App::$server) {
            ServerEnum::FPM => new Fpm(),
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