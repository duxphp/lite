<?php
declare(strict_types=1);


use Dux\App;
use Noodlehaus\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Utils\FileSystem;

class AppCommand extends Command {

    protected static $defaultName = 'ui:push';
    protected static $defaultDescription = 'Publish UI components to the client';


    public function execute(InputInterface $input, OutputInterface $output): int {
        UI::sync();
        $output->write("<info>Generate application successfully</info>");
        return Command::SUCCESS;
    }

    public function error(OutputInterface $output, string $message): int {
        $output->write("<error>$$message</error>");
        return Command::FAILURE;
    }

}