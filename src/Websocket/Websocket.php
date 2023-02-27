<?php
declare(strict_types=1);

namespace Dux\Websocket;

use Dux\Websocket\Handler\Client;
use Dux\Websocket\Handler\Event;
use Dux\App;
use Firebase\JWT\JWT;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;
use Workerman\Lib\Timer;
use function DI\string;

class Websocket
{
    public int $pingTime = 55;

    /**
     * PING 端
     * @var array
     */
    public array $pings = [];

    /**
     * 客户端
     * @var $clients Client[][]
     */
    public array $clients = [];

    /**
     * 客户端映射
     * @var $clients Client[]
     */
    public array $clientMaps = [];

    public function onWorkerStart(Worker $worker): void
    {
        // 心跳连接
        Timer::add($this->pingTime, function () use ($worker) {
            $time = time();
            foreach ($worker->connections as $connection) {
                $ping = $this->pings[$connection->id];
                if ($time - $ping > $this->pingTime) {
                    self::send($connection, 'offline', 'connection timeout');
                    $connection->close();
                } else {
                    self::send($connection, 'pong');
                }
            }
        });
    }

    public function onConnect(TcpConnection $connection): void
    {
        $connection->onWebSocketConnect = function ($connection, $httpBuffer) {
            $platform = (string)$_SERVER['PLATFORM'];
            $token = $_GET['token'];
            if (!$token) {
                self::send($connection, 'error', '授权参数未知');
                $connection->close();
                return;
            }
            try {
                // 授权解码
                $jwt = JWT::decode($token, \Dux\App::config("app")->get("app.secret"), ["HS256", "HS512", "HS384"]);
                if (!$jwt->sub || !$jwt->id) {
                    self::send($connection, 'error', '授权参数有误');
                    $connection->close();
                    return;
                }

                // 判断单点登录
                if ($this->clients[$jwt->sub][$jwt->id]) {
                    self::send($this->clients[$jwt->sub][$jwt->id]->connection, 'offline.login', '您的账号在其他地方登录');
                    $this->clients[$jwt->sub][$jwt->id]->connection->close();
                }

                // 设置功能类型与用户id
                $client = new Client($connection, $jwt->sub, $jwt->id);
                $client->platform = $platform;

                // 设置客户端信息
                $this->pings[$connection->id] = time();
                $this->clients[$jwt->sub][$jwt->id] = $client;
                $this->clientMaps[$connection->id] = $client;
                App::event()->dispatch(new Event($this, $connection), 'websocket.online');

                self::send($connection, 'connect', '', [
                    'has' => $jwt->sub,
                    'has_id' => $jwt->id
                ]);
            } catch (\Exception $e) {
                self::send($connection, 'error', $e->getMessage());
                $connection->close();
            }
        };

    }

    public function onMessage(TcpConnection $connection, $data): void
    {
        $worker = App::di()->get('ws.worker');

        // 更新心跳时间
        $this->pings[$connection->id] = time();

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
                if (!$this->clientMaps[$connection->id]) {
                    self::send($connection, 'error', '请先授权登录');
                    break;
                }
                // 触发消息事件
                try {
                    App::event()->dispatch(new Event($this, $connection, $params), 'websocket.' . $params['type']);
                } catch (\Exception $e) {
                    self::send($connection, 'error', $e->getMessage());
                }
        }

    }

    public function onClose(TcpConnection $connection): void
    {
        $client = $this->clientMaps[$connection->id];
        if (!$client) {
            return;
        }

        // 触发事件
        try {
            App::event()->dispatch(new Event($this, $connection), 'websocket.offline');
        } catch (\Exception $e) {
            App::log("websocket")->error($e->getMessage(), $e->getTrace());
        }

        // 卸载客户端数据
        unset($this->pings[$connection->id], $this->clients[$client->sub][$client->id], $this->clientMaps[$connection->id]);

        dux_debug($this->pings, $this->clients, $this->clientMaps);
    }


    public static function send(TcpConnection $connection, string $type, string $message = '', array $data = []): ?bool
    {
        $content = json_encode(['type' => $type, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
        return $connection->send($content);
    }
}