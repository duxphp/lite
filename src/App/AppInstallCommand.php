<?php
declare(strict_types=1);

namespace Dux\App;

use Dux\App;
use Nette\Utils\FileSystem;
use Noodlehaus\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppInstallCommand extends Command
{

    protected static $defaultName = 'app:install';
    protected static $defaultDescription = 'install applications in the system';


    protected function configure(): void
    {
        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            'please enter the package name'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        AppHandler::install($name, true);
        $output->writeln("<info>successfully installing the application</info>");
        return Command::SUCCESS;
    }

    public function error(OutputInterface $output, string $message): int
    {
        $output->writeln("<error>$message</error>");
        return Command::FAILURE;
    }

}