<?php

namespace Dux\Scheduler;

use Closure;
use Dux\App;
use Exception;
use Workerman\Crontab\Crontab;

class Scheduler
{
    private array $data = [];

    public function add(string $cron, callable|array $callback): void
    {
        $this->data[] = [
            'cron' => $cron,
            'func' => $callback
        ];
    }

    public function expand(): void
    {
        foreach ($this->data as $item) {
            new Crontab($item['cron'], function () use ($item) {
                $func = $item['func'];
                if ($item['func'] instanceof Closure) {
                    try {
                        $func();
                    } catch (Exception $e) {
                        App::log('scheduler')->error($e->getMessage());
                    }
                    return;
                }
                [$class, $method] = $func;
                if (!class_exists($class)) {
                    throw new Exception("Scheduler class [$class] does not exist");
                }
                if (!method_exists($class, $method)) {
                    throw new Exception("Scheduler method [$class:$method] does not exist");
                }

                try {
                    (new $class)->$method();
                } catch (Exception $e) {
                    App::log('scheduler')->error($e->getMessage());
                }

            });
        }
    }

}