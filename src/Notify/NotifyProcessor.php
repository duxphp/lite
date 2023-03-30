<?php

namespace Dux\Notify;

use Dux\Handlers\ExceptionBusiness;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;

class NotifyProcessor implements Processor
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
        //$data = ['type' => 'type', 'message' => '', 'data' => []];
        $this->data = json_decode($message->getBody(), true);

        $type = $this->data['type'];
        if (!$type) {
            throw new ExceptionBusiness('Message type error');
        }

        return self::ACK;
    }

    public function get(): array
    {
        return $this->data;
    }
}