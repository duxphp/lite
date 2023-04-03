<?php
declare(strict_types=1);

namespace Dux\Queue;

use Dux\Handlers\Exception;
use Redis;

class QueueHandlers
{
    private string $class;
    private string $method;
    private array $params;
    private int $delay = 0;

    public function __construct(public Redis $client, public string $group)
    {
    }

    /**
     * 设置回调类
     * @param string $class
     * @param string $method
     * @param array $params
     * @return QueueHandlers
     */
    public function callback(string $class, string $method = "", array $params = []): self
    {
        $this->class = $class;
        $this->method = $method;
        $this->params = $params;
        return $this;
    }


    /**
     * 设置延时任务
     * @param int $millisecond
     * @return QueueHandlers
     */
    public function delay(int $millisecond): self
    {
        $this->delay = $millisecond;
        return $this;
    }

    /**
     * 发送队列
     */
    public function send(): bool|int|Redis
    {
        if (!$this->class) {
            throw new Exception("Please set the callback class");
        }

        $queue_waiting = '{redis-queue}-waiting';
        $queue_delay = '{redis-queue}-delayed';
        $now = time();
        $package_str = json_encode([
            'id' => rand(),
            'time' => $now,
            'delay' => 0,
            'attempts' => 0,
            'queue' => $this->group,
            'data' => [[$this->class, $this->method], $this->params]
        ]);
        if ($this->delay) {
            return $this->client->zAdd($queue_delay, $now + $this->delay, $package_str);
        }
        return $this->client->lPush($queue_waiting . $this->group, $package_str);
    }


}