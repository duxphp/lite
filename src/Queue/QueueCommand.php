<?php
declare(strict_types=1);

namespace Dux\Queue;

use Dux\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Interop\Queue\Context;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\SignalExtension;

class QueueCommand extends Command {

    protected static $defaultName = 'queue';
    protected static $defaultDescription = 'Queue start service';
    private Context $context;
    private int $timeout;
    private int $retry;

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
        $this->timeout = (int)App::config("queue")->get("timeout", 10);
        $this->retry = (int)App::config("queue")->get("retry", 3);
        $this->context = App::queue()->context;
        $queueConsumer = new QueueConsumer($this->context, new ChainExtension([
            new SignalExtension(),
        ]));
        pcntl_async_signals(true);
        $queueConsumer->bind($name, new QueueProcessor($this->context->createQueue($name), $this->timeout, $this->retry));
        $queueConsumer->consume();
        return Command::SUCCESS;
    }

}