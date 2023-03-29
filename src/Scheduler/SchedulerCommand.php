<?php
declare(strict_types=1);

namespace Dux\Scheduler;

use Dux\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerCommand extends Command
{

    protected static $defaultName = 'scheduler';
    protected static $defaultDescription = 'scheduler start service';

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        App::scheduler()->run();
        return Command::SUCCESS;
    }
}