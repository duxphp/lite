<?php
declare(strict_types=1);

use Carbon\Carbon;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\VarDumper\VarDumper;

if (! function_exists('now')) {
    function now(): Carbon {
        return Carbon::now();
    }
}

if (! function_exists('dux_debug')) {
    function dux_debug(...$args): void {

        if (!in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) && !headers_sent()) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: *');
            header('Access-Control-Allow-Headers: *');
            http_response_code(500);
        }

        foreach ($args as $v) {
            VarDumper::dump($v);
        }
        exit(1);
    }
}

