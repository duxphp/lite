<?php

namespace Dux\Scheduler;

use Closure;
use Cron\CronExpression;
use DateTimeInterface;
use Dux\App;
use Exception;
use Orisai\Scheduler\Executor\ProcessJobExecutor;
use Orisai\Scheduler\Job\CallbackJob;
use Orisai\Scheduler\SimpleScheduler;
use Orisai\Scheduler\Status\JobInfo;
use Orisai\Scheduler\Status\JobResult;
use Throwable;

class Scheduler
{
    public ProcessJobExecutor $executor;
    public SimpleScheduler $scheduler;

    public function __construct()
    {
        $errorHandler = function(Throwable $throwable, JobInfo $info, JobResult $result): void {
            App::log('scheduler')->error("Job {$info->getName()} failed", [
                'exception' => $throwable,
                'expression' => $info->getExpression(),
                'start' => $info->getStart()->format(DateTimeInterface::ATOM),
                'end' => $result->getEnd()->format(DateTimeInterface::ATOM),
            ]);
        };

        $this->executor = new ProcessJobExecutor();
        $this->executor->setExecutable('dux');
        $this->scheduler = new SimpleScheduler($errorHandler, null, $this->executor);
    }

    private array $data = [];

    public function add(string $cron, callable|array $callback): void
    {
        $this->data[] = [
            'cron' => $cron,
            'func' => $callback
        ];
    }

    public function run(): void
    {
        $this->scheduler->run();
    }

        /**
     * @throws Exception
     */
    public function expand(): void
    {
        foreach ($this->data as $item) {
            $func = $item['func'];
            if ($item['func'] instanceof Closure) {
                $this->scheduler->addJob(
                    new CallbackJob($func),
                    new CronExpression($item['cron']),
                );
                continue;
            }
            [$class, $method] = $func;
            if (!class_exists($class)) {
                throw new Exception("Scheduler class [$class] does not exist");
            }
            if (!method_exists($class, $method)) {
                throw new Exception("Scheduler method [$class:$method] does not exist");
            }
            $this->scheduler->addJob(
                new CallbackJob(Closure::fromCallable([$class, $method])),
                new CronExpression($item['cron']),
            );
        }
    }

}