<?php

declare(strict_types=1);


namespace Dux;

use DI\Container;
use Dux\App\AppExtend;
use Dux\Database\Db;
use Dux\Database\Migrate;
use Dux\Handlers\Exception;
use Dux\Storage\Storage;
use Dux\View\View;
use Evenement\EventEmitter;
use \Slim\App as SlimApp;
use Dux\Logs\LogHandler;
use Dux\Queue\Queue;
use Dux\Validator\Validator;
use DI\DependencyException;
use DI\NotFoundException;
use Monolog\Level;
use Monolog\Logger;
use Noodlehaus\Config;
use Phpfastcache\Helper\Psr16Adapter;
use Symfony\Component\Console\Application;
use Twig\Environment;
use Illuminate\Database\Capsule\Manager as Capsule;

class App {
    static string $basePath;
    static string $configPath;
    static string $dataPath;
    static string $publicPath;
    static Bootstrap $bootstrap;
    static array $registerApp = [];


    static Migrate $dbMigrate;

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
        $app->loadEvent();
        $app->loadApp();
        self::$bootstrap = $app;
        return $app;
    }

    static function createCli($basePath): Bootstrap {
        $app = self::create($basePath);
        $app->loadCommand();
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
            if (!$vo instanceof AppExtend) {
                throw new Exception("The application $vo could not be registered");
            }
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
     * event
     * @return EventEmitter
     */
    static function event(): EventEmitter {
        return self::$bootstrap->event;
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
     * @source illuminate/database
     * @param string $type
     * @return Capsule
     */
    static function db(string $type = "default"): Capsule {
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
     * dbMigrate
     * @param string $type
     * @return Migrate
     */
    static function dbMigrate(string $type = "default"): Migrate {
        if (!$type) {
            $type = self::config("database")->get("db.type");
        }
        if (!self::$bootstrap->container->has("migrate." . $type)) {
            self::$bootstrap->container->set(
                "migrate." . $type,
                new Migrate(self::db($type))
            );
        }
        return self::$bootstrap->container->get("migrate." . $type);
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