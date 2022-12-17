<?php
declare(strict_types=1);

namespace Dux\Cache;

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Helper\Psr16Adapter;

class Cache {

    static function init(string $type, array $config): Psr16Adapter {
        if ($type == "files") {
            $config["path"] = __DIR__ . "/../../data/cache";
        }
        CacheManager::setDefaultConfig(new ConfigurationOption($config));
        return new Psr16Adapter($type);
    }
}