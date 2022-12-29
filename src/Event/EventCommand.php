<?php

namespace Dux\Event;

use Dux\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EventCommand extends Command {

    protected static $defaultName = 'event';
    protected static $defaultDescription = 'show event list';

    public function execute(InputInterface $input, OutputInterface $output): int {
        $list = App::event()->listeners();
        $data = [];
        foreach ($list as $name => $vo) {
            $data[] = [$name, count($vo)];
        }

        $table = new Table($output);
        $table
            ->setHeaders([
                ['Name', 'Num']
            ])
            ->setRows($data);
        $table->render();
        return Command::SUCCESS;
    }
}