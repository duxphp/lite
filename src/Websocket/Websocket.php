<?php
declare(strict_types=1);

namespace Dux\Websocket;

use Dux\App;
use Dux\Websocket\Handler\Client;
use Dux\Websocket\Handler\EventService;
use Exception;
use Firebase\JWT\JWT;
use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;
use Workerman\Worker;

class Websocket
{

    /**
     * PING 端
     * @var array
     */
    public static array $pings = [];

    /**
     * 客户端
     * @var $clients Client[][]
     */
    public static array $clients = [];

    /**
     * 客户端映射
     * @var $clients Client[]
     */
    public static array $clientMaps = [];

    public function onWorkerStart(Worker $worker): void
    {
        // 连接通讯组件
        $port = App::config('use')->get('app.port', 8080);
        $port = $port + 1;
        \Channel\Client::connect('0.0.0.0', $port);

        // 监听websocket 消息
        \Channel\Client::on('websocket', function ($channelData) use ($worker) {
            if (!self::$clients[$channelData['client_app']][$channelData['client_id']]) {
                return;
            }
            dump($channelData['message']);
            self::send(self::$clients[$channelData['client_app']][$channelData['client_id']]->connection, $channelData['type'], $channelData['message'], $channelData['data']);
        });


        // 心跳连接
        Timer::add(30, function () use ($worker) {
            $time = time();
            foreach ($worker->connections as $connection) {
                $ping = self::$pings[$connection->id];
                if ($time - $ping > 60) {
                    App::log('websocket')->error('connection timeout');
                    self::send($connection, 'offline', 'connection timeout');
                    $connection->close();
                } else {
                    self::send($connection, 'pong');
                }
            }
        });
        App::event()->dispatch(new EventService($this), 'websocket.start');

    }

    public function onConnect(TcpConnection $connection): void
    {
        $connection->onWebSocketConnect = function (TcpConnection $connection, $httpBuffer) {
            $platform = (string)$_SERVER['PLATFORM'];
            $token = $_GET['token'];
            if (!$token) {
                self::send($connection, 'error', '授权参数未知');
                $connection->close();
                return;
            }
            try {
                // 授权解码
                $jwt = JWT::decode($token, App::config("use")->get("app.secret"), ["HS256", "HS512", "HS384"]);
                if (!$jwt->sub || !$jwt->id) {
                    self::send($connection, 'error', '授权参数有误');
                    $connection->close();
                    return;
                }

                // 判断单点登录
                if (self::$clients[$jwt->sub][$jwt->id]) {
                    //self::$clients[$jwt->sub][$jwt->id]->connection->close("\x88\x02\x27\x10", true);
                }

                // 设置功能类型与用户id
                $client = new Client($connection, $jwt->sub, $jwt->id, $platform);

                // 发送连接消息
                Message::send((string)$jwt->sub, (string)$jwt->id, 'connect', '', [
                    'has' => $jwt->sub,
                    'has_id' => $jwt->id
                ]);

                // 设置客户端信息
                self::$pings[$connection->id] = time();
                self::$clients[$jwt->sub][$jwt->id] = $client;
                self::$clientMaps[$connection->id] = $client;

                // 触发上线事件
                App::event()->dispatch(new MessageEvent("message", $client->sub, (string)$client->id, [], $client->platform), 'message.online');

            } catch (Exception $e) {
                App::log('websocket')->error($e->getMessage());
                self::send($connection, 'error', $e->getMessage());
                $connection->close();
            }
        };

    }

    public function onMessage(TcpConnection $connection, $data): void
    {
        $worker = App::di()->get('ws.worker');

        // 更新心跳时间
        self::$pings[$connection->id] = time();

        if (!isset($worker->connections[$connection->id])) {
            self::send($connection, 'offline', '连接已断开，请重新登录');
            return;
        }
        $params = json_decode($data, true);
        if (!$params['type']) {
            self::send($connection, 'error', '消息格式错误');
            return;
        }

        switch ($params['type']) {
            case 'ping':
                break;
            default:
                // 判断授权
                if (!self::$clientMaps[$connection->id]) {
                    self::send($connection, 'error', '请先授权登录');
                    break;
                }

                // 触发消息事件
                try {
                    $client = self::$clientMaps[$connection->id];
                    $data = [
                        'type' => $params['type'],
                        'message' => $params['message'] ?: null,
                        'data' => $params['data'] ?: null
                    ];
                    App::event()->dispatch(new MessageEvent("message", $client->sub, (string)$client->id, $data, $client->platform), "message." . $params['type']);
                } catch (Exception $e) {
                    self::send($connection, 'error', $e->getMessage());
                }
        }

    }

    public function onClose(TcpConnection $connection): void
    {
        $client = self::$clientMaps[$connection->id];
        if (!$client) {
            return;
        }

        try {
            // 触发事件
            App::event()->dispatch(new MessageEvent("message", $client->sub, (string)$client->id, [], $client->platform), 'message.offline');
        } catch (Exception $e) {
            App::log("websocket")->error($e->getMessage(), $e->getTrace());
        }

        // 卸载客户端数据
        unset(self::$pings[$connection->id], self::$clients[$client->sub][$client->id], self::$clientMaps[$connection->id]);
    }


    public static function send(TcpConnection $connection, string $type, string|array $message = '', array $data = []): ?bool
    {
        $content = json_encode(['type' => $type, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
        return $connection->send($content);
    }
}