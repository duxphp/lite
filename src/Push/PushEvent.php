<?php

namespace Dux\Push;

use Symfony\Contracts\EventDispatcher\Event;

class PushEvent extends Event
{

    public function __construct(
        // 用户应用
        public string $clientApp,
        // 用户ID
        public string $clientId,
        // 消息数据
        public array  $data = [],
        // 平台信息
        public string $platform = 'web',
    )
    {
    }

}