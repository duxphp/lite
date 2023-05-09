<?php
declare(strict_types=1);

namespace Dux\Scheduler;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Worker;

class SchedulerCommand extends Command
{

    protected static $defaultName = 'scheduler';
    protected static $defaultDescription = 'scheduler start service';

    protected function configure(): void
    {
        $this->setName('scheduler')
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
        \Dux\Server\Handlers\Scheduler::start(false);
        Worker::runAll();
        return Command::SUCCESS;
    }
}