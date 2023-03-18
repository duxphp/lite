<?php
declare(strict_types=1);

namespace Dux\Push;

use Dux\App;
use Enqueue\Redis\RedisConnectionFactory;
use Exception;
use Interop\Queue\Context;

class Push
{

    public Context $context;

    /**
     * @var array []\Interop\Queue\Queue
     */
    public array $services = [];

    private bool $supportDelay = true;

    public function __construct(string $type, array $config)
    {
        $factory = match ($type) {
            "redis" => new RedisConnectionFactory($config),
            default => throw new Exception('This driver is not supported')
        };
        $this->context = $factory->createContext();
    }

    public function topic(string $name, string $clientApp, string $clientId): PushHandlers
    {
        $topicName = "$name.$clientApp.$clientId";
        if (App::di()->has("push.$topicName")) {
            $topic = App::di()->get("push.$topicName");
        } else {
            $topic = $this->context->createTopic($name);
            App::di()->set("push.$topicName", $topic);
        }
        return new PushHandlers($this->context, $topic, $name, $clientApp, $clientId);
    }

}