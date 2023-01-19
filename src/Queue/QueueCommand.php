<?php
declare(strict_types=1);

namespace Dux\Queue;

use Dux\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueCommand extends Command {

    protected static $defaultName = 'queue';
    protected static $defaultDescription = 'Queue start service';
    public array $retryData = [];

    protected function configure(): void {
        $this->addArgument(
            'group',
            InputArgument::OPTIONAL,
            'enter a queue name to run a different queue'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln("start queue task");

        $name = $input->getArgument('group') ?: "default";
        $timeout = (int)App::config("queue")->get("timeout");
        $retry = (int)App::config("queue")->get("retry", 3);

        $queue = App::queue()->context->createQueue($name);
        $consumer = App::queue()->context->createConsumer($queue);
        do {
            $message = $consumer->receive();
            pcntl_signal(SIGALRM, function () use ($consumer, $message, $retry) {
                $this->retry($message, $consumer, $retry);
            });
            pcntl_alarm($timeout);
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
                $consumer->acknowledge($message);
            } catch (\Exception $error) {
                App::log("queue")->error($error->getMessage(), [$error->getFile() . ":" . $error->getLine()]);
                $this->retry($message, $consumer, $retry);
            }
            pcntl_alarm(0);
        } while (1);
    }


    public function retry(\Interop\Queue\Message $message, \Interop\Queue\Consumer $consumer, int $retry) {
        $id = $message->getMessageId();
        $retryNum = $this->retryData[$id] ?: 0;
        $retryNum++;
        $consumer->reject($message, $retryNum <= $retry);
        if ($retryNum > $retry) {
            unset($this->retryData[$id]);
            $body = $message->getBody();
            App::log("queue")->error("task [$body] retry failed");
        }else {
            $this->retryData[$id] = $retryNum;
        }
    }
}