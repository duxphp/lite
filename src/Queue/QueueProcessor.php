<?php

namespace Dux\Queue;

use Dux\App;
use Interop\Queue\Processor;
use Interop\Queue\Message;
use Interop\Queue\Context;

class QueueProcessor implements Processor {


    public function __construct(
        public \Interop\Queue\Queue $queue,
        public int $timeout,
        public int $retry
    ) {}

    public function process(Message $message, Context $context): object|string {
        pcntl_signal(SIGALRM, function () use ($message) {
            $body = $message->getBody();
            App::log("queue")->error("task [$body] timeout");
            $this->retry($message, $this->queue);
        });
        pcntl_alarm(5);
        try {
            $body = $message->getBody();
            [$class, $method] = explode(":", $body, 2);
            if (!class_exists($class)) {
                App::log("queue")->error("class [{$class}]  does not exist");
            } else {
                $object = new $class;
                if (!$method) {
                    $object(...$message->getProperties());
                } else if (method_exists($object, $method)) {
                    $object->$method(...$message->getProperties());
                } else {
                    App::log("queue")->error("method [{$body}]  does not exist");
                }
            }
        } catch (\Exception $error) {
            App::log("queue")->error($error->getMessage(), [$error->getFile() . ":" . $error->getLine()]);
            $this->retry($message, $queue);
        }
        pcntl_alarm(0);
        return Processor::ACK;


    }


    public function retry(Message $message, \Interop\Queue\Queue $queue) {
        $id = $message->getMessageId();
        $retryNum = $message->getHeader("retry_num", 0);
        $retryNum++;
        $body = $message->getBody();
        if ($retryNum > $this->retry) {
            App::log("queue")->error("task [$body] retry failed");
        }else {
            $message->setHeader("retry_num", $retryNum);
            $this->context->createProducer()->send($queue, $message);
        }
    }
}