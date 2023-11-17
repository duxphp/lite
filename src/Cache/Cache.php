<?php
declare(strict_types=1);

namespace Dux\Cache;

use Dux\App;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Drivers\Redis\Config;
use Phpfastcache\Helper\Psr16Adapter;

class Cache
{

    public static function init(string $type, array $config): Psr16Adapter
    {
        if ($type === "files") {
            $config = new ConfigurationOption([
                'path' => App::$dataPath . "/cache"
            ]);
        }
        if ($type === "redis") {
            $config = new Config([
                'host' => $config['host'],
                'port' => (int)$config['port'],
                'timeout' => (int)$config['timeout'],
                'password' => $config['auth'] ?: '',
                'database' => (int)$config['database'] ?: 0,
                'optPrefix' => $config['optPrefix'] ?: '',
            ]);
        }
        return new Psr16Adapter($type, $config);
    }
}