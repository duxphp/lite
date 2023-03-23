<?php

namespace Dux\Database\DbDrives;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;

class Fpm implements DriveInterface
{
    public Manager $capsule;

    public function init(array $configs): void
    {
        $capsule = new Manager;
        foreach ($configs as $key => $config) {
            $capsule->addConnection($config, $key);
        }
        $capsule->setAsGlobal();
        $capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->bootEloquent();
        $this->capsule = $capsule;
    }

    public function get(): Manager
    {
        return $this->capsule;
    }

    public function release(): void
    {
    }

}