<?php
declare(strict_types=1);

namespace Dux\App;

use Dux\App;
use Nette\Utils\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppInstallCommand extends Command {

    protected static $defaultName = 'app:install';
    protected static $defaultDescription = 'install applications in the system';


    protected function configure(): void {
        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            'please enter the package name'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int {
        $name = $input->getArgument('name');
        $dir = base_path("vendor/$name");
        if (!is_dir($dir)) {
            return $this->error($output, 'The application already exists');
        }
        $composerFile = $dir . '/composer.json';
        if (!is_file($composerFile)) {
            return $this->error($output, 'The application configuration does not exist');
        }
        $config = json_decode(file_get_contents($composerFile), true);
        $extra = $config['extra'];
        $duxExtra = $extra['dux'] ?: [];

        $app = false;
        foreach ($duxExtra as $target => $source) {
            if ($target == 'app') {
                $app = true;
            }
            if (is_array($source)) {
                $ignore = $source['ignore'];
                $sourceDir = $source['dir'];
            }else {
                $ignore = false;
                $sourceDir = $source;
            }
            $list = glob("$dir/$sourceDir/*");
            foreach ($list as $vo) {
                $relativeDir = $target . "/" . basename($vo);
                $targetDir = base_path($relativeDir);
                if ($ignore && (is_dir($targetDir) || is_file($targetDir))) {
                    continue;
                }
                FileSystem::copy($vo, $targetDir);
                $output->writeln("<fg=green>  - Add $relativeDir </>");
            }
        }
        if ($app) {
            $command = $this->getApplication()->find('db:sync');
            $greetInput = new ArrayInput([]);
            $command->run($greetInput, $output);
        }

        $output->writeln("<fg=green>successfully installing the application</>");
        return Command::SUCCESS;
    }

    public function error(OutputInterface $output, string $message): int {
        $output->writeln("<error>$message</error>");
        return Command::FAILURE;
    }

}