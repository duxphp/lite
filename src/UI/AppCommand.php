<?php
declare(strict_types=1);


namespace Dux\UI;

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
        $output->writeln("<info>The UI file is successfully synchronized</info>");
        return Command::SUCCESS;
    }

}