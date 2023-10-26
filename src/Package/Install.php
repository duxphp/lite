<?php

namespace Dux\Package;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Install
{
    public static function main(InputInterface $input, OutputInterface $output, SymfonyStyle $io, string $username, string $password, string $app): void
    {

        // 获取云端包
        $cloudPackages = collect(Package::app($username, $password, $app));


        $io->success('Add Application Success');
    }

}