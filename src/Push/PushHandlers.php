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

    public function send(string $content): void
    {
        App::event()->dispatch(new PingEvent($this->name, $this->clientApp, $this->clientId), "push.$this->name");
        $message = $this->context->createMessage($content);
        $this->context->createProducer()->send($this->topic, $message);
    }

    public function consume(): array
    {
        App::event()->dispatch(new PingEvent($this->name, $this->clientApp, $this->clientId), "push.$this->name");
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