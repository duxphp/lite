<?php

declare(strict_types=1);


namespace Dux;

use DI\Container;
use Dux\Database\Db;
use Dux\Storage\Storage;
use Dux\View\View;
use Psr\Http\Message\ServerRequestInterface;
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
use Twig\Environment;

class App {
    static string $basePath;
    static string $configPath;
    static string $dataPath;
    static string $publicPath;
    static Bootstrap $bootstrap;
    static array $registerApp = [];
    static array $registerRoute = [];

    /**
     * create
     * @param $basePath
     * @return Bootstrap
     */
    static function create($basePath): Bootstrap {

        self::$basePath = $basePath;
        self::$configPath = $basePath . '/config';
        self::$dataPath = $basePath . '/data';
        self::$publicPath = $basePath . '/public';

        $app = new Bootstrap();
        $app->loadFunc();
        $app->loadWeb();
        $app->loadConfig();
        $app->loadCache();
        $app->loadView();
        $app->loadRoute();
        self::$bootstrap = $app;
        return $app;
    }

    static function createCli($basePath): Bootstrap {
        $app = self::create($basePath);
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
     * di
     * @return Container
     */
    static function di(): Container {
        return self::$bootstrap->container;
    }

    /**
     * command
     * @return Application
     */
    static function command(): Application {
        return self::$bootstrap->command;
    }

    /**
     * getDebug
     * @return bool
     */
    static function getDebug(): bool {
        return self::$bootstrap->debug;
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
                LogHandler::init($app, Level::Debug)
            );
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
                new Queue($queueType, $config)
            );
        }
        return self::$bootstrap->container->get("queue." . $type);
    }

    /**
     * view
     * @param string $name
     * @param string $path
     * @return Environment
     * @throws DependencyException
     * @throws NotFoundException
     */
    static function view(string $name, string $path): Environment {
        if (!self::$bootstrap->container->has("view." . $name)) {
            self::$bootstrap->container->set(
                "view." . $name,
                View::init($name, $path)
            );
        }
        return self::$bootstrap->container->get("view." . $name);
    }

    /**
     * storage
     * @param string $type
     * @return mixed
     */
    static function storage(string $type = "") {
        if (!$type) {
            $type = self::config("storage")->get("type");
        }
        if (!self::$bootstrap->container->has("storage." . $type)) {
            $config = self::config("storage")->get("drivers." . $type);
            $storageType = $config["type"];
            unset($config["type"]);
            self::$bootstrap->container->set(
                "storage." . $type,
                Storage::init($storageType, $config)
            );
        }
        return self::$bootstrap->container->get("storage." . $type);
    }


}