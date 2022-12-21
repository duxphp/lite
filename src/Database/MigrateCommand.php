<?php
declare(strict_types=1);

namespace Dux\Database;

use Dux\App;
use Enqueue\Redis\RedisMessage;
use Enqueue\Redis\RedisConsumer;
use \InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command {

    protected static $defaultName = 'db:sync';
    protected static $defaultDescription = 'Synchronize model data tables and fields';

    public function execute(InputInterface $input, OutputInterface $output): int {
        App::dbMigrate()->migrate();
        $output->write("<info>Sync database successfully</info>");
        return Command::SUCCESS;
    }

}