<?php

declare(strict_types=1);


namespace Dux;

use DI\Container;
use Dux\App\AppExtend;
use Dux\Database\Db;
use Dux\Database\MedooExtend;
use Dux\Database\Migrate;
use Dux\Handlers\Exception;
use Dux\Storage\Storage;
use Dux\View\View;
use Evenement\EventEmitter;
use Medoo\Medoo;
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

class App {
    static string $basePath;
    static string $configPath;
    static string $dataPath;
    static string $publicPath;
    static Bootstrap $bootstrap;
    static Container $di;
    static array $config;
    static array $registerApp = [];

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
        self::$di = new Container();

        $app = new Bootstrap();
        $app->loadFunc();
        $app->loadWeb(self::$di);
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
    static function config(string $name): Config {
        if (self::$di->has("config.".$name)) {
            return self::$di->get("config.".$name);
        }
        $config = new Config(App::$configPath . "/$name.yaml");
        self::$di->set("config.".$name, $config);
        return $config;
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
        return self::$di;
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
     * @return MedooExtend
     */
    static function db(string $type = ""): MedooExtend {
        if (!$type) {
            $type = self::config("database")->get("db.type", "default");
        }
        if (!self::$di->has("db." . $type)) {
            self::$di->set(
                "db." . $type,
                Db::init(self::config("database")->get("db.drivers.". $type))
            );
        }
        return self::$di->get("db." . $type);
    }

    /**
     * dbMigrate
     * @param string $type
     * @return Migrate
     */
    static function dbMigrate(string $type = ""): Migrate {
        if (!$type) {
            $type = self::config("database")->get("db.type", "default");
        }
        if (!self::$di->has("migrate." . $type)) {
            self::$di->set(
                "migrate." . $type,
                new Migrate(self::db($type))
            );
        }
        return self::$di->get("migrate." . $type);
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
        if (!self::$di->has("logger." . $app)) {
            self::$di->set(
                "logger." . $app,
                LogHandler::init($app, Level::Debug)
            );
        }
        return self::$di->get("logger." . $app);
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
        if (!self::$di->has("queue." . $type)) {
            $config = self::config("queue")->get("drivers." . $type);
            $queueType = $config["type"];
            unset($config["type"]);
            self::$di->set(
                "queue." . $type,
                new Queue($queueType, $config)
            );
        }
        return self::$di->get("queue." . $type);
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
        if (!self::$di->has("view." . $name)) {
            self::$di->set(
                "view." . $name,
                View::init($name, $path)
            );
        }
        return self::$di->get("view." . $name);
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
        if (!self::$di->has("storage." . $type)) {
            $config = self::config("storage")->get("drivers." . $type);
            $storageType = $config["type"];
            unset($config["type"]);
            self::$di->set(
                "storage." . $type,
                Storage::init($storageType, $config)
            );
        }
        return self::$di->get("storage." . $type);
    }


}