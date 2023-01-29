<?php
declare(strict_types=1);

use Carbon\Carbon;
use Symfony\Component\VarDumper\VarDumper;


if (!function_exists('base_path')) {
    function base_path(string $path = ""): string {
        return sys_path(\Dux\App::$basePath, $path);
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ""): string {
        return sys_path(\Dux\App::$appPath, $path);
    }
}

if (!function_exists('data_path')) {
    function data_path(string $path = ""): string {
        return sys_path(\Dux\App::$dataPath, $path);
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ""): string {
        return sys_path(\Dux\App::$publicPath, $path);
    }
}

if (!function_exists('config_path')) {
    function config_path(string $path = ""): string {
        return sys_path(\Dux\App::$configPath, $path);
    }
}

if (!function_exists('sys_path')) {
    function sys_path(string $base = "", string $path = ""): string {
        $base = rtrim(str_replace("\\", "/", $base), "/");
        $path = str_replace("\\", "/", $path ? "/" . $path : "");
        return $base . $path;
    }
}

if (!function_exists('now')) {
    function now(): Carbon {
        return Carbon::now();
    }
}

if (!function_exists('dux_debug')) {
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
    }
}


if (!function_exists('get_ip')) {
    function get_ip() {
        if (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        }
        if (getenv('HTTP_X_REAL_IP')) {
            $ip = getenv('HTTP_X_REAL_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
            $ips = explode(',', $ip);
            $ip = $ips[0];
        } elseif (getenv('REMOTE_ADDR')) {
            $ip = getenv('REMOTE_ADDR');
        } else {
            $ip = '0.0.0.0';
        }
        return $ip;
    }
}

if (!function_exists('bc_math')) {
    function bc_math(int|float|string $left = 0, string $symbol = '+', int|float|string $right = 0, int $default = 2): string {
        bcscale($default);
        return match ($symbol) {
            '+' => bcadd((string)$left, (string)$right),
            '-' => bcsub((string)$left, (string)$right),
            '*' => bcmul((string)$left, (string)$right),
            '/' => bcdiv((string)$left, (string)$right),
            '%' => bcmod((string)$left, (string)$right),
        };
    }
}