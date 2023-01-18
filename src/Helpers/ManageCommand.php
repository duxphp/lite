<?php
declare(strict_types=1);

namespace Dux\Helpers;

use Dux\App;
use Dux\Validator\Data;
use Noodlehaus\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Utils\FileSystem;
use Symfony\Component\Console\Question\Question;

class ManageCommand extends Command {

    protected static $defaultName = 'generate:manage';
    protected static $defaultDescription = 'Create an manage controller';

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
        if (!is_dir($dir)) {
            return $this->error($output, 'The application does not exist');
        }
        $helper = $this->getHelper('question');

        $question = new Question("Please enter a dir name: ", false);
        $dirName = $helper->ask($input, $output, $question);
        if (!$dirName) {
            return $this->error($output, "The dir name is not entered");
        }
        $dirName = ucwords($dirName);
        $dirPath = "$dir/$dirName";

        $question = new Question("Please enter a class name: ", false);
        $className = $helper->ask($input, $output, $question);
        if (!$className) {
            return $this->error($output, "The class name is not entered");
        }
        $className = ucwords($className);

        try {
            if (!is_dir($dirPath)) {
                FileSystem::createDir($dirPath);
            }
        } catch (\Exception $exception) {
            return $this->error($output, 'Directory creation failure');
        }

        $file = new \Nette\PhpGenerator\PhpFile;
        $file->setStrictTypes();
        $namespace = $file->addNamespace("App\\$name\\$dirName");
        $namespace->addUse(\Dux\Manage\Manage::class);
        $namespace->addUse(Data::class);
        $class = $namespace->addClass($className);
        $class->addProperty("model", "")->setProtected();
        $class->addProperty("name", "业务名")->setProtected();
        $class->setExtends(\Dux\Manage\Manage::class);

        $method = $class->addMethod("listFormat")->setReturnType("array")->setBody('return [
            "id" => $item->id,
        ];');
        $method->addParameter("item")->setType("object");

        $method = $class->addMethod("infoFormat")->setReturnType("array")->setBody('return ["info" => [
            "id" => $item->id,
        ]];');
        $method->addParameter("info")->setType("object");

        $method = $class->addMethod("saveValidator")->setReturnType("array")->setBody('return [
            "name" => ["required", "请输入名称"],
        ];');
        $method->addParameter("args")->setType("array");

        $method = $class->addMethod("saveFormat")->setReturnType("array")->setBody('return [
            "name" => $data->name,
        ];');
        $method->addParameter("data")->setType(Data::class);
        $method->addParameter("id")->setType("int");

        $content = (new \Nette\PhpGenerator\PsrPrinter)->printFile($file);
        FileSystem::write("$dirPath/$className.php", $content);

        $output->writeln("<info>Generate manage successfully</info>");
        return Command::SUCCESS;
    }

    public function error(OutputInterface $output, string $message): int {
        $output->writeln("<error>$$message</error>");
        return Command::FAILURE;
    }


}