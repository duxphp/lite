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
use Interop\Queue\Exception;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
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
     * @param string|array|null $message
     * @param array|null $data 消息数据
     * @return void
     * @throws Exception
     * @throws InvalidDestinationException
     * @throws InvalidMessageException
     */
    public function send(string $type, string|array|null $message, ?array $data): void
    {
        App::event()->dispatch(new PushEvent($this->name, $this->clientApp, $this->clientId, [
            'type' => $type,
            'message' => $message,
            'data' => $data
        ]), "subscribe.$this->name.$type");
        $messageCtx = $this->context->createMessage(json_encode([
            'type' => $type,
            'message' => $message,
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->context->createProducer()->send($this->topic, $messageCtx);
    }

    public function consume(): array
    {
        App::event()->dispatch(new PushEvent($this->name, $this->clientApp, $this->clientId, []), "subscribe.$this->name.ping");

        $queueConsumer = new QueueConsumer($this->context, new ChainExtension([
            new LimitConsumptionTimeExtension(new DateTime('now + 10 sec')),
            new LimitConsumedMessagesExtension(1)
        ]));
        $processor = new PushProcessor($this->name, $this->clientApp, $this->clientId);
        $queueConsumer->bind($this->topic->getTopicName(), $processor);
        $queueConsumer->consume();
        return $processor->get();
    }

}