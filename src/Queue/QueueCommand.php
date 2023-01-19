<?php
declare(strict_types=1);

namespace Dux\Queue;

use Dux\App;
use Interop\Queue\Consumer;
use Interop\Queue\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Interop\Queue\Context;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\SignalExtension;
use Spatie\Async\Pool;

class QueueCommand extends Command {

    protected static $defaultName = 'queue';
    protected static $defaultDescription = 'Queue start service';
    private \Interop\Queue\Queue $queue;
    private Consumer $consumer;

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
        $timeout = (int)App::config("queue")->get("timeout", 10);
        $retry = (int)App::config("queue")->get("retry", 3);
        $context = App::queue()->context;
        $queueConsumer = new QueueConsumer($context, new ChainExtension([
            new SignalExtension(),
        ]));
        $queueConsumer->bind($name, new QueueProcessor($context->createQueue($name), $timeout, $retry));
        $queueConsumer->consume();
        return Command::SUCCESS;
    }
}