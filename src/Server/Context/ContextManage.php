<?php

namespace Dux\Server\Context;

use Swow\Coroutine;
use WeakMap;

final class ContextManage
{
    private static WeakMap $map;

    public static function getContext(Coroutine $coroutine): Context
    {
        self::$map ??= new WeakMap();
        return self::$map[$coroutine] ??= new Context();
    }

    public static function context(): Context
    {
        return self::getContext(Coroutine::getCurrent());
    }
}
