<?php

declare(strict_types=1);


namespace Dux;

use DI\Container;
use Dux\App\AppExtend;
use Dux\Database\Db;
use Dux\Database\Migrate;
use Dux\Event\Event;
use Dux\Handlers\Exception;
use Dux\Storage\Storage;
use Dux\Validator\Data;
use Dux\View\View;
use Illuminate\Database\Capsule\Manager;
use Latte\Engine;
use League\Flysystem\Filesystem;
use Redis;
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

class App {
    public static string $basePath;
    public static string $configPath;
    public static string $dataPath;
    public static string $publicPath;
    public static string $appPath;
    public static Bootstrap $bootstrap;
    public static Container $di;
    public static array $config;
    public static array $registerApp = [];

    /**
     * create
     * @param $basePath
     * @return Bootstrap
     */
    public static function create($basePath): Bootstrap {
        self::$basePath = $basePath;
        self::$configPath = $basePath . '/config';
        self::$dataPath = $basePath . '/data';
        self::$publicPath = $basePath . '/public';
        self::$appPath = $basePath . '/app';

        self::$di = new Container();

        $app = new Bootstrap();
        $app->loadFunc();
        $app->loadConfig();
        $app->loadWeb(self::$di);
        $app->loadCache();
        $app->loadView();
        $app->loadEvent();
        $app->loadDb();
        $app->loadApp();
        $app->loadRoute();
        self::$bootstrap = $app;
        return $app;
    }

    public static function createCli($basePath): Bootstrap {
        $app = self::create($basePath);
        $app->loadCommand();
        return $app;
    }

    /**
     * @return SlimApp
     */
    public static function app(): SlimApp {
        return self::$bootstrap->web;
    }

    /**
     * 注册应用
     * @param array $class
     * @return void
     */
    public static function registerApp(array $class): void {
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
    public static function config(string $name): Config {
        if (self::$di->has("config." . $name)) {
            return self::$di->get("config." . $name);
        }
        $config = new Config(App::$configPath . "/$name.yaml", new \Dux\Config\Yaml());
        self::$di->set("config." . $name, $config);
        return $config;
    }

    /**
     * cache
     * @source PHPSocialNetwork/phpfastcache
     * @return Psr16Adapter
     */
    public static function cache(): Psr16Adapter {
        return self::$bootstrap->cache;
    }

    /**
     * event
     * @return Event
     */
    public static function event(): Event {
        return self::$bootstrap->event;
    }

    public static function listener(): Logger {
        if (!self::$di->has("dispatch")) {
            self::$di->set(
                "dispatch",
                LogHandler::init($app, Level::Debug)
            );
        }
        return self::$di->get("logger." . $app);
    }

    /**
     * di
     * @return Container
     */
    public static function di(): Container {
        return self::$di;
    }

    /**
     * command
     * @return Application
     */
    public static function command(): Application {
        return self::$bootstrap->command;
    }

    /**
     * getDebug
     * @return bool
     */
    public static function getDebug(): bool {
        return self::$bootstrap->debug;
    }

    /**
     * validator
     * @source nette/utils
     * @param array $data data array
     * @param array $rules ["name", "rule", "message"]
     * @return Data
     */
    public static function validator(array $data, array $rules): Data {
        return Validator::parser($data, $rules);
    }

    /**
     * database
     * @source illuminate/database
     * @return Manager
     */
    public static function db(): Manager {
        if (!self::$di->has("db")) {
            self::$di->set(
                "db",
                Db::init(self::config("database")->get("db.drivers"))
            );
        }
        return self::$di->get("db");
    }

    /**
     * dbMigrate
     * @return Migrate
     */
    public static function dbMigrate(): Migrate {
        if (!self::$di->has("db.migrate")) {
            self::$di->set(
                "db.migrate",
                new Migrate()
            );
        }
        return self::$di->get("db.migrate");
    }

    /**
     * log
     * @source Seldaek/monolog
     * @param string $app
     * @return Logger
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function log(string $app = "default"): Logger {
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
    public static function queue(string $type = ""): Queue {
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
     * @return Engine
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function view(string $name): Engine {
        if (!self::$di->has("view." . $name)) {
            self::$di->set(
                "view." . $name,
                View::init($name)
            );
        }
        return self::$di->get("view." . $name);
    }

    /**
     * storage
     * @param string $type
     * @return Filesystem
     */
    public static function storage(string $type = ""): Filesystem {
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

    /**
     * menu
     * @param string $name
     * @return Menu\Menu
     */
    public static function menu(string $name): Menu\Menu {
        return self::$bootstrap->getMenu()->get($name);
    }

    /**
     * permission
     * @param string $name
     * @return Permission\Permission
     */
    public static function permission(string $name): Permission\Permission {
        return self::$bootstrap->getPermission()->get($name);
    }

    /**
     * redis
     * @param string $name
     * @return Redis
     */
    public static function redis($database = 0, string $name = "default"): Redis {
        if (!self::$di->has("redis." . $name)) {
            $config = self::config("database")->get("redis.drivers." . $name);
            $redis = (new \Dux\Database\Redis($config))->connect();
            self::$di->set(
                "redis." . $name,
                $redis
            );
        }
        $redis = self::$di->get("redis." . $name);
        $redis->select($database);
        return $redis;
    }


}