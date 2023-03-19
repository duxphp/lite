<?php

namespace Dux\Push;

use Dux\App;
use Symfony\Contracts\EventDispatcher\Event;

class PushEvent extends Event
{

    public function __construct(
        // 订阅主题
        public string $topic,
        // 用户应用
        public string $clientApp,
        // 用户ID
        public string $clientId,
        // 消息数据 ['type' => '', 'message' => '', 'data' => []]
        public array  $data = [],
        // 平台信息
        public string $platform = 'web',
    )
    {
    }

    public function send(string $type, string|array|null $message, ?array $data = [])
    {
        App::push()->topic($this->topic, $this->clientApp, $this->clientId)->send($type, $message, $data);
    }

}