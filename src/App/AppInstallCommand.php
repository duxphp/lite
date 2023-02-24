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
use Symfony\Component\Console\Question\ChoiceQuestion;

class AppInstallCommand extends Command
{

    protected static $defaultName = 'app:install';
    protected static $defaultDescription = 'install applications in the system';


    protected function configure(): void
    {
        $this->addArgument(
            'name',
            InputArgument::OPTIONAL,
            'please enter the package name'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {

        $name = $input->getArgument('name');

        if (!$name) {

            $config = json_decode(file_get_contents("./composer.lock"), true);

            $list = array_values(array_filter(array_map(function ($item) {
                if ($item['type'] === 'dux-app') {
                    return $item['name'];
                }
            }, $config["packages"])));

            $helper = $this->getHelper('question');

            $question = new ChoiceQuestion(
                'Please select Install application ("," split multiple)',
                $list,
            );

            $question->setMultiselect(true);
            $question->setErrorMessage('Application %s is invalid.');

            $selecteds = $helper->ask($input, $output, $question);
            if (!$selecteds) {
                return $this->error("The installation application is not selected");
            }

            foreach ($selecteds as $selected) {
                $output->writeln("install application <info>$selected</info>");
                AppHandler::install($selected);
            }

        }else {
            AppHandler::install($name);
        }


        $output->writeln("<info>successfully installing the application</info>");
        return Command::SUCCESS;
    }

    public function error(OutputInterface $output, string $message): int
    {
        $output->writeln("<error>$message</error>");
        return Command::FAILURE;
    }

}