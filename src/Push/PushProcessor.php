<?php

namespace Dux\Push;

use Dux\App;
use Exception;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;

class PushProcessor implements Processor
{

    public array $data = [];

    public function __construct(
        public string $name,
        public string $clientApp,
        public string $clientId
    )
    {
    }

    public function process(Message $message, Context $context): object|string
    {
        $this->data = json_decode($message->getBody(), true);

        // 事件触发
        App::db()->getConnection()->beginTransaction();
        try {
            App::event()->dispatch(new PushEvent($this->name, $this->clientApp, $this->clientId, $this->data), "push.$this->name");
            App::db()->getConnection()->commit();
            return self::ACK;
        } catch (Exception $e) {
            App::db()->getConnection()->rollBack();
            return self::REJECT;
        }
    }

    public function get(): array
    {
        return $this->data;
    }
}