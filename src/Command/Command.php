<?php

namespace Dux\Command;

use Symfony\Component\Console\Application;
class Command {

    static function init(array $commands = []): Application {
        $application = new Application();
        foreach ($commands as $command) {
            $application->add(new $command);
        }
        return $application;
    }



}