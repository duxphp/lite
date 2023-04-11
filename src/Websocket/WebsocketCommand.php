<?php

namespace Dux\Websocket;

use Dux\Server\Handlers\Channel;
use Dux\Server\Handlers\Websocket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Worker;

ini_set('memory_limit', '1024M');

class WebsocketCommand extends Command
{

    protected static $defaultName = 'websocket';
    protected static $defaultDescription = 'start websocket service';

    protected function configure(): void
    {
        $this->setName('websocket')
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
        Channel::start();
        Websocket::start();
        Worker::runAll();
        return Command::SUCCESS;
    }
}
