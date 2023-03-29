<?php

namespace Dux\Scheduler;

use Closure;
use Dux\App;
use Exception;
use GO\Job;

class Scheduler
{

    private \GO\Scheduler $scheduler;

    public function __construct()
    {
        $this->scheduler = new \GO\Scheduler();
    }

    public function add(callable|array $callback, $params = []): Job
    {
        if ($callback instanceof Closure) {
            return $this->scheduler->call($callback, $params);
        }

        [$class, $method] = $callback;
        if (!class_exists($class)) {
            throw new Exception("Scheduler class [$class] does not exist");
        }
        if (!method_exists($class, $method)) {
            throw new Exception("Scheduler method [$class:$method] does not exist");
        }
        return $this->scheduler->call(function () use ($class, $method, $params) {
            (new $class)->$method(...$params);
        });
    }

    public function run(): void
    {
        $failedJobs = $this->scheduler->getFailedJobs();
        foreach ($failedJobs as $job) {
            App::log('scheduler')->error($job->getException()->getMessage() . ' ' . $job->getException()->getFile() . ':' . $job->getException()->getLine());
        }
        $this->scheduler->run();
    }

    public function work(array $seconds = [0]): void
    {
        $this->scheduler->work($seconds);
    }

}