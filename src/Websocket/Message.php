<?php
declare(strict_types=1);

namespace Dux\Websocket;

use Channel\Client;

class Message
{

    /**
     * 通用消息发送
     * @param string $clientApp
     * @param string $clientId
     * @param string $type
     * @param string|array|null $message
     * @param array|null $data
     * @return void
     */
    public static function send(string $clientApp, string $clientId, string $type, string|array|null $message, ?array $data = []): void
    {
        Client::publish('websocket', [
            'client_app' => $clientApp,
            'client_id' => $clientId,
            'type' => $type,
            'message' => $message,
            'data' => $data
        ]);

//        if (!Websocket::$clients[$clientApp][$clientId]) {
//            return;
//        }
//        Websocket::send(Websocket::$clients[$clientApp][$clientId]->connection, $type, $message, $data);
    }


}