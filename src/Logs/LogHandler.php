<?php
declare(strict_types=1);

namespace Dux\Logs;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

class LogHandler {

    public static function init(string $name, Level $level): Logger {
        $log = new Logger($name);
        $log->pushHandler(new StreamHandler(__DIR__ . '/../../data/logs/' . $name . '.log', $level));
        return $log;
    }
}