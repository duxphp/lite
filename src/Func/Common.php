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

if (!function_exists('bc_format')) {
    function bc_format(int|float|string $value = 0, int $decimals = 2): string {
        return number_format((float) $value, $decimals, '.', '');
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

if (!function_exists('bc_comp')) {
    function bc_comp(int|float|string $left = 0, int|float|string $right = 0, int $scale = 2): int {
        return bccomp((string)$left, (string)$right, $scale);
    }
}


if (!function_exists('encryption')) {
    function encryption(string $str, string $key = '', string $iv = '', $method = 'DES-CBC'): string {
        $key = $key ?: \Dux\App::config('use')->get('app.secret');
        $data = openssl_encrypt($str, $method, $key, OPENSSL_RAW_DATA, $iv);
        return strtolower(bin2hex($data));
    }
}


if (!function_exists('decryption')) {
    function decryption(string $str, string $key = '', string $iv = '', $method = 'DES-CBC'): string {
        $key = $key ?: \Dux\App::config('use')->get('app.secret');
        return openssl_decrypt(hex2bin($str), $method, $key, OPENSSL_RAW_DATA, $iv);
    }
}


if (!function_exists('start_time')) {

    $start_time = microtime(true);
}



if (!function_exists('end_time')) {

    function end_time() {
        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time) * 1000;
    }
}