<?php
declare(strict_types=1);

namespace Dux\Cache;

use Dux\App;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Helper\Psr16Adapter;

class Cache
{

    public static function init(string $type, array $config): Psr16Adapter
    {
        if ($type === "files") {
            $config["path"] = App::$dataPath . "/cache";
        }
        return new Psr16Adapter($type, new ConfigurationOption($config));
    }
}