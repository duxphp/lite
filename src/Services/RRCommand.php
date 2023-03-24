<?php

namespace Dux\Services;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;


class RRCommand extends Command
{

    protected static $defaultName = 'rr';
    protected static $defaultDescription = 'start web rr service';

    protected function configure(): void
    {
        $this->addOption(
            'bin',
            null,
            InputOption::VALUE_REQUIRED,
            'RoadRunner bin path'
        );


        $this->addOption(
            'port',
            null,
            InputOption::VALUE_REQUIRED,
            'http port'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $bin = $input->getOption("bin");
        if (!$bin) {
            $bin .= './rr';
        }

        $configPath = realpath(__DIR__ . '/RoadRunner/rr.yaml');

        $port = $input->getOption("port") ?: 8080;

        $p = Process::fromShellCommandline("$bin serve c $configPath -o=http.address=0.0.0.0:$port ", null, []);
        $p->setWorkingDirectory(getcwd());
        $p->setTimeout(null);
        $p->run(function ($type, $out) use ($output) {
            $output->write($out);
        });

        return Command::SUCCESS;
    }
}
