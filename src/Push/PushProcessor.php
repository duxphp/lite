<?php

namespace Dux\Push;

use Dux\Handlers\ExceptionBusiness;
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
        //$data = ['type' => 'type', 'message' => '', 'data' => []];
        $this->data = json_decode($message->getBody(), true);

        $type = $this->data['type'];
        if (!$type) {
            throw new ExceptionBusiness('Message type error');
        }
    }

    public function get(): array
    {
        return $this->data;
    }
}