<?php
declare(strict_types=1);

namespace Dux\Config;

class Config {
    static array $variables = [];

    public static function setValue(string $key, mixed $value): void {
        self::$variables[$key] = $value;
    }

    public static function getValue(string $key): mixed {
        return self::$variables[$key];
    }
}