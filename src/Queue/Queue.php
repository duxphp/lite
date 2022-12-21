<?php
declare(strict_types=1);

namespace Dux\Queue;
use Dux\Handlers\Exception;
use Enqueue\Redis\RedisConnectionFactory;
use Interop\Queue\Context;
use RuntimeException;

class Queue {

    public Context $context;

    private bool $supportDelay = true;

    public function __construct(string $type, array $config) {
        if ($type !== "redis") {
            throw new RuntimeException("Queue type not supported");
        }
        $factory = new RedisConnectionFactory($config);
        $this->context = $factory->createContext();
    }

    public function add(string $group = ""): QueueHandlers {
        return new QueueHandlers($this->context, $this->supportDelay, $group);
    }

}