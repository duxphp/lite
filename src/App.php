<?php

declare(strict_types=1);


namespace Dux;

use Dux\Database\Db;
use \Slim\App as SlimApp;
use Dux\Logs\LogHandler;
use Dux\Queue\Queue;
use Dux\Validator\Validator;
use DI\DependencyException;
use DI\NotFoundException;
use Medoo\Medoo;
use Monolog\Level;
use Monolog\Logger;
use Noodlehaus\Config;
use Phpfastcache\Helper\Psr16Adapter;
use Symfony\Component\Console\Application;

class App {

    static Bootstrap $bootstrap;
    static array $registerApp = [];

    static array $registerRoute = [];

    /**
     * create
     * @return Bootstrap
     */
    static function create(): Bootstrap {
        $app = new Bootstrap();
        $app->loadWeb();
        $app->loadConfig();
        $app->loadCache();
        $app->loadRoute();
        self::$bootstrap = $app;
        return $app;
    }

    static function createCli(): Bootstrap {
        self::create();
        $app = new Bootstrap();
        $app->loadWeb();
        $app->loadConfig();
        $app->loadCache();
        $app->loadRoute();
        $app->loadCommand();
        self::$bootstrap = $app;
        return $app;
    }

    /**
     * @return SlimApp
     */
    static function app(): SlimApp {
        return self::$bootstrap->web;
    }

    /**
     * 注册应用
     * @param array $class
     * @return void
     */
    static function registerApp(array $class): void {
        foreach ($class as $vo) {
            self::$registerApp[] = $vo;
        }
    }

    /**
     * config
     * @source noodlehaus/config
     * @param string $app
     * @return Config
     */
    static function config(string $app): Config {
        return self::$bootstrap->config[$app];
    }

    /**
     * cache
     * @source PHPSocialNetwork/phpfastcache
     * @return Psr16Adapter
     */
    static function cache(): Psr16Adapter {
        return self::$bootstrap->cache;
    }


    /**
     * command
     * @return Application
     */
    static function command(): Application {
        return self::$bootstrap->command;
    }

    /**
     * validator
     * @source nette/utils
     * @param array $data data array
     * @param array $rules ["name", "rule", "message"]
     * @return array
     */
    static function validator(array $data, array $rules): array {
        return Validator::parser($data, $rules);
    }

    /**
     * database
     * @source catfan/Medoo
     * @param string $type
     * @return Medoo
     */
    static function db(string $type = ""): Medoo {
        if (!$type) {
            $type = self::config("database")->get("db.type");
        }
        if (!self::$bootstrap->container->has("db." . $type)) {
            self::$bootstrap->container->set(
                "db." . $type,
                Db::init(self::config("database")->get("db.drivers.". $type))
            );
        }
        return self::$bootstrap->container->get("db." . $type);
    }

    /**
     * log
     * @source Seldaek/monolog
     * @param string $app
     * @return Logger
     * @throws DependencyException
     * @throws NotFoundException
     */
    static function log(string $app = "default"): Logger {
        if (!self::$bootstrap->container->has("logger." . $app)) {
            self::$bootstrap->container->set(
                "logger." . $app,
                LogHandler::init($app, Level::Debug));
        }
        return self::$bootstrap->container->get("logger." . $app);
    }

    /**
     * queue
     * @param string $type
     * @return Queue
     * @throws DependencyException
     * @throws NotFoundException
     */
    static function queue(string $type = ""): Queue {
        if (!$type) {
            $type = self::config("queue")->get("type");
        }
        if (!self::$bootstrap->container->has("queue." . $type)) {
            $config = self::config("queue")->get("drivers." . $type);
            $queueType = $config["type"];
            unset($config["type"]);
            self::$bootstrap->container->set(
                "queue." . $type,
                new Queue($queueType, $config));
        }
        return self::$bootstrap->container->get("queue." . $type);
    }
}