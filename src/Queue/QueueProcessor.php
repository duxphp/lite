<?php
declare(strict_types=1);

namespace Dux\Queue;

use Dux\App;
use Exception;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;

class QueueProcessor implements Processor
{

    public function __construct(
        public \Interop\Queue\Queue $queue,
        public int                  $retry
    )
    {
    }

    public function process(Message $message, Context $context): object|string
    {
        try {
            $body = $message->getBody();
            [$class, $method] = explode(":", $body, 2);
            App::log("queue")->error("class [{$class}]  start");
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
        } catch (Exception $error) {
            App::log("queue")->error($error->getMessage(), [$error->getFile() . ":" . $error->getLine()]);
            $this->retry($message, $context);
        }
        return Processor::ACK;
    }

    public function retry(Message $message, Context $context): void
    {
        $retryNum = $message->getHeader("retry_num", 0);
        $retryNum++;
        $body = $message->getBody();
        if ($retryNum > $this->retry) {
            App::log("queue")->error("task [$body] retry failed");
        } else {
            $message->setHeader("retry_num", $retryNum);
            $context->createProducer()->send($this->queue, $message);
        }
    }
}