<?php
declare(strict_types=1);

namespace Dux\Push;

use DateTime;
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
    public function send(string $type, string|array|null $message, ?array $data = []): void
    {
        $messageCtx = $this->context->createMessage(json_encode([
            'type' => $type,
            'message' => $message,
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->context->createProducer()->setTimeToLive(null)->send($this->topic, $messageCtx);
    }

    public function consume(int $timeout = 0): array
    {
        $middle = [
            new LimitConsumedMessagesExtension(1)
        ];
        if ($timeout) {
            $middle[] = new LimitConsumptionTimeExtension(new DateTime("now + $timeout sec"));
        }
        $queueConsumer = new QueueConsumer($this->context, new ChainExtension($middle));
        $processor = new PushProcessor($this->name, $this->clientApp, $this->clientId);
        $queueConsumer->bind($this->topic->getTopicName(), $processor);
        $queueConsumer->consume();
        return $processor->get();
    }

    public function unsubscribe(): void
    {
        $this->context->deleteTopic($this->topic);
    }

}