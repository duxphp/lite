<?php
declare(strict_types=1);

namespace Dux\Package;

use Illuminate\Support\Collection;
use Nette\Utils\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class UninstallCommand extends Command
{

    protected static $defaultName = 'uninstall';
    protected static $defaultDescription = 'Install the application';

    protected function configure(): void
    {
        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            'please enter the app name'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');


        Uninstall::main($input, $output, $io, $name);

        return Command::SUCCESS;
    }

}