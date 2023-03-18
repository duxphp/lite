<?php
declare(strict_types=1);

namespace Dux\Push;

use DateTime;
use Dux\App;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\QueueConsumer;
use Interop\Queue\Context;
use Interop\Queue\Topic;

class PushHandlers
{

    public function __construct(
        public Context $context,
        public Topic   $topic,
        public string  $name,
        public string  $clientApp,
        public string  $clientId)
    {
    }

    /**
     * @param string $type 事件类型
     * @param array $data 消息数据
     * @return void
     */
    public function send(string $type, string|array $message, array $data): void
    {
        App::event()->dispatch(new PushEvent($this->name, $this->clientApp, $this->clientId, []), "subscribe.$this->name.ping");
        $messageCtx = $this->context->createMessage([
            'type' => $type,
            'message' => $message,
            'data' => $data
        ]);
        $this->context->createProducer()->send($this->topic, $messageCtx);
    }

    public function consume(): array
    {
        App::event()->dispatch(new PushEvent($this->name, $this->clientApp, $this->clientId, []), "subscribe.$this->name.ping", []);
        $queueConsumer = new QueueConsumer($this->context, new ChainExtension([
            new LimitConsumptionTimeExtension(new DateTime('now + 3 sec')),
            new LimitConsumedMessagesExtension(1)
        ]));
        $processor = new PushProcessor($this->name, $this->clientApp, $this->clientId);
        $queueConsumer->bind($this->topic->getTopicName(), $processor);
        $queueConsumer->consume();
        return $processor->get();
    }

}