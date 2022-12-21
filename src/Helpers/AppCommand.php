<?php
declare(strict_types=1);

namespace Dux\Helpers;

use Dux\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Utils\FileSystem;

class AppCommand extends Command {

    protected static $defaultName = 'make:app';
    protected static $defaultDescription = 'Create an application module';
    public array $retryData = [];

    protected function configure(): void {
        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            'please enter the application name'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int {
        $name = $input->getArgument('name');
        $name = ucfirst($name);
        $dir = App::$appPath . "/$name";
        if (is_dir($dir)) {
            return $this->error($output, 'The application already exists');
        }
        try {
            FileSystem::createDir($dir);
        }catch (\Exception $exception) {
            return $this->error($output, 'Application creation failure');
        }

        $file = new \Nette\PhpGenerator\PhpFile;
        $file->setStrictTypes();

        $namespace = $file->addNamespace("App\\" . $name);

        $class = $namespace->addClass($name);
        $class->setExtends(App\AppExtend::class);
        $class->addComment("Application Registration");

        $class->addProperty("name", "App Name")->setType("string");
        $class->addProperty("description", "App Desc")->setType("string");

        $content = (new \Nette\PhpGenerator\PsrPrinter)->printFile($file);
        file_put_contents($dir . "/App.php", $content);

        $output->writeln("<success>Generate application successfully</success>");
        return Command::SUCCESS;
    }

    public function error(OutputInterface $output, string $message): int {
        $output->writeln("<error>$$message</error>");
        return Command::FAILURE;
    }

}