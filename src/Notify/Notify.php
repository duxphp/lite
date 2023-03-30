<?php

namespace Dux\Notify;

use Dux\App;
use Redis;

class Notify
{

    public function subscribe(string $channel)
    {
        $data = [];
        App::redis()->subscribe($channel, function (Redis $redis, $channel, $message) use (&$data) {
            $data = json_decode($message);
            $redis->unsubscribe($channel);
        });
        return $data;
    }

}