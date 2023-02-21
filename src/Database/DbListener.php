<?php

namespace Dux\Database;

use Dux\App;
use Dux\Server\ServerEnum;
use Dux\Server\ServerEvent;

class DbListener
{

    /**
     * 服务启动
     * @param ServerEvent $event
     * @return void
     */
    public function start(ServerEvent $event): void
    {
        if ($event->server === ServerEnum::WORKERMAN) {
            \Workerman\Timer::add(55, static function () {
                // Db Heartbeat
                foreach (App::db()->getDatabaseManager()->getConnections() as $connection) {
                    if ($connection->getConfig('driver') === 'mysql') {
                        try {
                            $connection->select('select 1');
                        } catch (\Throwable $e) {}
                    }
                }
            });
        }

    }

}