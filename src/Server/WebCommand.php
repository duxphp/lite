<?php
declare(strict_types=1);

namespace Dux\Server;

use Dux\Server\Handlers\Queue;
use Dux\Server\Handlers\Web;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Worker;

class WebCommand extends Command
{

    protected static $defaultName = 'web';
    protected static $defaultDescription = 'start web service';

    protected function configure(): void
    {
        $this->setName('web')
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
        Web::Start();
        Queue::start();

        Worker::runAll();

        return Command::SUCCESS;
    }
}