<?php

namespace Dux\Coroutine;

use Swoole\Coroutine;
use WeakMap;

class ContextManage
{
    public static WeakMap $map;

    public static function init(): void
    {
        self::$map ??= new WeakMap();
    }


    public static function context(): Context
    {
        $context = Coroutine::getContext();
        if (!isset(self::$map[$context])) {
            self::$map[$context] = new Context();
        }

        return self::$map[$context];
    }

    public static function destroy(): void
    {
        $context = Coroutine::getContext();
        if (!isset(self::$map[$context])) {
            return;
        }
        //sleep(5);
        self::$map[$context]->destroy();
        unset(self::$map[$context]);
    }

}