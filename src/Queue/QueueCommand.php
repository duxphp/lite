<?php
declare(strict_types=1);

namespace Dux\Queue;

use Spatie\Async\Pool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Worker;

class QueueCommand extends Command
{

    protected static $defaultName = 'queue';
    protected static $defaultDescription = 'Queue start service';

    protected function configure(): void
    {
        $this->setName('queue')
            ->addArgument(
                'args',
                InputArgument::IS_ARRAY
            )->addOption(
                'daemon',
                'd',
                InputOption::VALUE_OPTIONAL
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        \Dux\Server\Handlers\Queue::start(false);
        Worker::runAll();
        return Command::SUCCESS;
    }
}