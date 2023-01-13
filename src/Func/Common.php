<?php
declare(strict_types=1);

use Carbon\Carbon;

if (! function_exists('now')) {
    function now(): Carbon {
        return Carbon::now();
    }
}

if (! function_exists('dux_debug')) {
    function dux_debug(...$args) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Headers: *');
        http_response_code(500);
        dump(...$args);
        die(1);
    }
}

