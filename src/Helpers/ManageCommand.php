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
        $appName = $input->getArgument('name');
        $appName = ucfirst($appName);
        $appDir = App::$appPath . "/$appName";
        if (!is_dir($appDir)) {
            return $this->error($output, 'The application does not exist');
        }
        $helper = $this->getHelper('question');

        $question = new Question("Please enter a dir name: ", false);
        $layerName = $helper->ask($input, $output, $question);
        if (!$layerName) {
            return $this->error($output, "The dir name is not entered");
        }
        $layerName = ucwords($layerName);
        $dirPath = "$appDir/$layerName";

        $question = new Question("Please enter a class name: ", false);
        $className = $helper->ask($input, $output, $question);
        if (!$className) {
            return $this->error($output, "The class name is not entered");
        }
        $className = ucwords($className);

        // manage
        $managePath = "$dirPath/$className.php";
        $file = new \Nette\PhpGenerator\PhpFile;
        $file->setStrictTypes();
        $namespace = $file->addNamespace("App\\$appName\\$layerName");
        $namespace->addUse(\Dux\Manage\Manage::class);
        $namespace->addUse(Data::class);
        $class = $namespace->addClass($className);
        $class->addProperty("model", "")->setType("string")->setProtected();
        $class->addProperty("name", "业务名")->setType("string")->setProtected();
        $class->setExtends(\Dux\Manage\Manage::class);

        $method = $class->addMethod("listFormat")->setReturnType("array")->setBody('return [
    "id" => $item->id,
];')->setProtected();
        $method->addParameter("item")->setType("object");

        $method = $class->addMethod("infoFormat")->setReturnType("array")->setBody('return [
"info" => [
    "id" => $info->id,
]];')->setProtected();
        $method->addParameter("info")->setType("object");

        $method = $class->addMethod("saveValidator")->setReturnType("array")->setBody('return [
    "name" => ["required", "请输入名称"],
];')->setProtected();
        $method->addParameter("args")->setType("array");

        $method = $class->addMethod("saveFormat")->setReturnType("array")->setBody('return [
    "name" => $data->name,
];')->setProtected();
        $method->addParameter("data")->setType(Data::class);
        $method->addParameter("id")->setType("int");
        FileSystem::write($managePath, (string)$file);

        // route
        $this->createRoute($appName, $appDir, $layerName, $className);

        // permission
        $this->createPermission($appName, $appDir, $layerName, $className);

        // jsx
        $this->createJsx($appName, $appDir, $layerName, $className);

        $output->writeln("<info>Generate manage successfully</info>");
        return Command::SUCCESS;
    }

    private function createRoute($appName, $appDir, $layerName, $className) {
        $filePath = "$appDir/Config/Route.php";
        $methodName = "Auth" . $layerName;
        $method = null;
        if (is_file($filePath)) {
            $file = \Nette\PhpGenerator\PhpFile::fromCode(file_get_contents($filePath));
            $classList = $file->getClasses();
            $class = $classList["App\\$appName\\Config\\Route"];
            if ($class->hasMethod($methodName)) {
                $method = $class->getMethod($methodName);
            }
        } else {
            $file = new \Nette\PhpGenerator\PhpFile;
            $file->setStrictTypes();
            $namespace = $file->addNamespace("App\\$appName\\Config");
            $namespace->addUse(\Dux\Route\Route::class, "DuxRoute");
            $class = $namespace->addClass("Route");
        }
        if (!$method) {
            $method = $class->addMethod($methodName)->setReturnType("void")->setStatic();
            $method->addParameter("route")->setType(\Dux\Route\Route::class);
        }
        $classNamespace = "App\\$appName\\$layerName\\$className";
        $name = lcfirst($appName) . "." . lcfirst($className);
        $pattern = "/" . str_replace(".", "/", $name);
        $method->addBody("\n" . '$route->manage(pattern: "' . $pattern . '", class: \\' . $classNamespace . '::class, name: "' . $name . '", title: "");');
        FileSystem::write($filePath, (string)$file);
    }

    private function createPermission($appName, $appDir, $layerName, $className) {
        $filePath = "$appDir/Config/Permission.php";
        $methodName = $layerName;
        $method = null;
        if (is_file($filePath)) {
            $file = \Nette\PhpGenerator\PhpFile::fromCode(file_get_contents($filePath));
            $classList = $file->getClasses();
            $class = $classList["App\\$appName\\Config\\Permission"];
            if ($class->hasMethod($methodName)) {
                $method = $class->getMethod($methodName);
            }
        } else {
            $file = new \Nette\PhpGenerator\PhpFile;
            $file->setStrictTypes();
            $namespace = $file->addNamespace("App\\$appName\\Config");
            $namespace->addUse(\Dux\Permission\Permission::class, "DuxPermission");
            $class = $namespace->addClass("Permission");
        }
        if (!$method) {
            $method = $class->addMethod($methodName)->setReturnType("void")->setStatic();
            $method->addParameter("permission")->setType(\Dux\Permission\Permission::class);
        }
        $name = lcfirst($appName) . "." . lcfirst($className);
        $method->addBody("\n\n" . '$group = $permission->manage("", "' . $name . '");');
        FileSystem::write($filePath, (string)$file);
    }


    private function createJsx($appName, $appDir, $layerName, $className) {
        $fileDir = "$appDir/Client/" . lcfirst($layerName) . "/" . lcfirst($className);
        $routeUrl = lcfirst($appName) . "/" . lcfirst($className);
        $name = lcfirst($appName) . "." . lcfirst($className);
        $pageUrl = $routeUrl . "/page";
        $listJsx = file_get_contents(__DIR__ . '/Tpl/list.jsx');
        $listJsx = str_replace("{{routeUrl}}", $routeUrl, $listJsx);
        $listJsx = str_replace("{{pageUrl}}", $pageUrl, $listJsx);
        $listJsx = str_replace("{{name}}", $name, $listJsx);
        FileSystem::write($fileDir . "/list.jsx", (string)$listJsx);

        $formJsx = file_get_contents(__DIR__ . '/Tpl/form.jsx');
        $formJsx = str_replace("{{routeUrl}}", $routeUrl, $formJsx);
        $formJsx = str_replace("{{pageUrl}}", $pageUrl, $formJsx);
        FileSystem::write($fileDir . "/form.jsx", (string)$formJsx);
    }

    public function error(OutputInterface $output, string $message): int {
        $output->writeln("<error>$$message</error>");
        return Command::FAILURE;
    }


}