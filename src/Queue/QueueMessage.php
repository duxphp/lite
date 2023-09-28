<?php
declare(strict_types=1);

namespace Dux\Queue;

use Enqueue\Redis\RedisMessage;
use Symfony\Component\Messenger\MessageBus;

class QueueMessage
{
    private RedisMessage $redisMessage;

    public function __construct(
        public MessageBus $bus,
        public string     $class,
        public string     $method = "",
        public array      $params = []
    )
    {
        $this->redisMessage = new RedisMessage($class . ':' . $method, $params);
    }

    public function delay($second = 0): self
    {
        $this->redisMessage->setDeliveryDelay($second);
        return $this;
    }

    public function send(): void
    {
        $this->bus->dispatch($this->redisMessage);
    }
}