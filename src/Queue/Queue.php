<?php
declare(strict_types=1);

namespace Dux\Queue;

use Enqueue\Redis\RedisConnectionFactory;
use Interop\Queue\Context;
use RuntimeException;

class Queue {

    public Context $context;

    /**
     * @var array []\Interop\Queue\Queue
     */
    public array $group = [];

    private bool $supportDelay = true;

    public function __construct(string $type, array $config) {
        if ($type !== "redis") {
            throw new RuntimeException("Queue type not supported");
        }
        $factory = new RedisConnectionFactory($config);
        $this->context = $factory->createContext();
    }

    public function add(string $group = "default"): QueueHandlers {
        if (isset($this->group[$group])) {
            $queue = $this->group[$group];
        } else {
            $queue = $this->context->createQueue($group);
            $this->group[$group] = $queue;
        }
        return new QueueHandlers($this->context, $queue, $this->supportDelay);
    }

}