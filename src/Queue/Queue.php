<?php
declare(strict_types=1);

namespace Dux\Queue;
use Enqueue\Redis\RedisConnectionFactory;
use Interop\Queue\Context;
use RuntimeException;

class Queue {

    public Context $context;

    public function __construct(string $type, array $config) {
        if ($type !== "redis") {
            throw new RuntimeException("Queue type not supported");
        }
        $factory = new RedisConnectionFactory($config);
        $this->context = $factory->createContext();
    }

    /**
     * @param string $class 类名
     * @param string $method 方法名
     * @param array $params 参数
     * @param int $delay 延迟毫秒
     * @param string $group 队列分组
     * @return void
     * @throws \Interop\Queue\Exception
     * @throws \Interop\Queue\Exception\DeliveryDelayNotSupportedException
     * @throws \Interop\Queue\Exception\InvalidDestinationException
     * @throws \Interop\Queue\Exception\InvalidMessageException
     */
    public function addQueue(string $class, string $method, array $params = [], int $delay = 0, string $group = "default") {
        $queue = $this->context->createQueue($group);
        $message = $this->context->createMessage($class."@".$method, $params);
        $this->context->createProducer()->setDeliveryDelay($delay)->send($queue, $message);
    }


}