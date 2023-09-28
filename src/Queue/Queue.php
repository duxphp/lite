<?php
declare(strict_types=1);

namespace Dux\Queue;

use Enqueue\Redis\RedisMessage;
use Redis;
use RuntimeException;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

class Queue
{
    public Redis $client;

    public array $config = [];

    public array $group = [];
    private MessageBus $bus;

    public function __construct(string $type, array $config)
    {
        if ($type !== "redis") {
            throw new RuntimeException("Queue type not supported");
        }
        $this->config = $config;

        $handler = new QueueHandlers();
        $bus = new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([
                QueueMessage::class => [$handler],
            ])),
        ]);

        $this->bus = $bus;
    }

    public function add(string $class, string $method = "", array $params = []): void
    {
        $message = new RedisMessage($class . ':' . $method, $params);
        $this->bus->dispatch($message);
    }

    public function process(): void
    {
    }

}