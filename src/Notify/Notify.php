<?php
declare(strict_types=1);

namespace Dux\Notify;

use Channel\Client;
use Psr\Http\Message\ResponseInterface;

class Notify
{
    public function __construct()
    {
    }

    // 消费订阅消息
    public function consume(ResponseInterface $response, string $topic): ResponseInterface
    {
        if (!is_service()) {
            sleep(10);
        }
        return send($response, 'ok')->withHeader('notify', $topic);
    }

    /**
     * 发送订阅消息
     * @param string $topic
     * @param array $data
     * @return void
     */
    public function send(string $topic, array $data): void
    {
        if (!is_service()) {
            return;
        }
        Client::publish("notify", [
            'topic' => $topic,
            'data' => $data
        ]);
    }

}