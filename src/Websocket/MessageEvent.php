<?php

namespace Dux\Websocket;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * 消息事件
 */
class MessageEvent extends Event
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

    /**
     * 发送消息给当前用户
     * @param string $type
     * @param string|array|null $message
     * @param array|null $data
     * @return void
     */
    public function send(string $type, string|array|null $message, ?array $data = [])
    {
        Message::send($this->clientApp, $this->clientId, $type, $message, $data);
    }

}