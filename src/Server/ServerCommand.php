<?php
declare(strict_types=1);

namespace Dux\Server;

use Dux\Server\Handlers\Queue;
use Dux\Server\Handlers\Web;
use Dux\Server\Handlers\Websocket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Worker;

class ServerCommand extends Command
{

    protected static $defaultName = 'server';
    protected static $defaultDescription = 'start server service';

    protected function configure(): void
    {
        $this->setName('server')
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
        Websocket::start();
        Queue::start();
        Worker::runAll();
        return Command::SUCCESS;
    }
}