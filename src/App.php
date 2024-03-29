<?php

declare(strict_types=1);


namespace Dux;

use Clockwork\Authentication\NullAuthenticator;
use Clockwork\Clockwork;
use Clockwork\Storage\FileStorage;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Dotenv\Dotenv;
use Dux\App\AppExtend;
use Dux\Auth\AuthService;
use Dux\Config\Yaml;
use Dux\Database\Db;
use Dux\Database\Migrate;
use Dux\Event\Event;
use Dux\Handlers\Exception;
use Dux\Logs\LogHandler;
use Dux\Notify\Notify;
use Dux\Queue\Queue;
use Dux\Scheduler\Scheduler;
use Dux\Storage\Storage;
use Dux\Validator\Data;
use Dux\Validator\Validator;
use Dux\View\View;
use Illuminate\Database\Capsule\Manager;
use Latte\Engine;
use League\Flysystem\Filesystem;
use Monolog\Level;
use Monolog\Logger;
use Noodlehaus\Config;
use Phpfastcache\Helper\Psr16Adapter;
use Redis;
use Slim\App as SlimApp;
use Symfony\Component\Console\Application;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

class App
{
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
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function create($basePath): Bootstrap
    {
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

    public static function createCli($basePath): Bootstrap
    {
        $app = self::create($basePath);
        $app->loadCommand();
        return $app;
    }

    /**
     * @return SlimApp
     */
    public static function app(): SlimApp
    {
        return self::$bootstrap->web;
    }

    /**
     * 注册应用
     * @param array $class
     * @return void
     */
    public static function registerApp(array $class): void
    {
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
     * @param string $name
     * @return Config
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function config(string $name): Config
    {
        if (self::$di->has("config." . $name)) {
            return self::$di->get("config." . $name);
        }

        $dotenv = Dotenv::createImmutable(self::$basePath);
        $dotenv->safeLoad();

        $file = App::$configPath . "/$name.dev.yaml";
        if (!is_file($file)) {
            $file = App::$configPath . "/$name.yaml";
        }
        $config = new Config($file, new Yaml());
        self::$di->set("config." . $name, $config);
        return $config;
    }

    /**
     * cache
     * @source PHPSocialNetwork/phpfastcache
     * @return Psr16Adapter
     */
    public static function cache(): Psr16Adapter
    {
        return self::$bootstrap->cache;
    }

    /**
     * event
     * @return Event
     */
    public static function event(): Event
    {
        return self::$bootstrap->event;
    }

    /**
     * di
     * @return Container
     */
    public static function di(): Container
    {
        return self::$di;
    }

    /**
     * command
     * @return Application
     */
    public static function command(): Application
    {
        return self::$bootstrap->command;
    }

    /**
     * getDebug
     * @return bool
     */
    public static function getDebug(): bool
    {
        return self::$bootstrap->debug;
    }

    /**
     * validator
     * @source https://github.com/vlucas/valitron
     * @param array $data data array
     * @param array $rules ["name", "rule", "message"]
     * @return Data
     */
    public static function validator(array $data, array $rules): Data
    {
        return Validator::parser($data, $rules);
    }

    /**
     * database
     * @source illuminate/database
     * @return Manager
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function db(): Manager
    {
        if (!self::$di->has("db")) {
            self::di()->set(
                "db",
                Db::init(App::config("database")->get("db.drivers"))
            );
        }
        return self::$di->get("db");
    }

    /**
     * dbMigrate
     * @return Migrate
     */
    public static function dbMigrate(): Migrate
    {
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
    public static function log(string $app = "default"): Logger
    {
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
    public static function queue(string $type = ""): Queue
    {
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
    public static function view(string $name): Engine
    {
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
    public static function storage(string $type = ""): Filesystem
    {
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
    public static function menu(string $name): Menu\Menu
    {
        return self::$bootstrap->getMenu()->get($name);
    }

    /**
     * permission
     * @param string $name
     * @return Permission\Permission
     */
    public static function permission(string $name): Permission\Permission
    {
        return self::$bootstrap->getPermission()->get($name);
    }

    /**
     * redis
     * @param string $name
     * @return Redis
     */
    public static function redis($database = 0, string $name = "default"): Redis
    {
        if (!self::$di->has("redis." . $name)) {
            $config = self::config("database")->get("redis.drivers." . $name);
            $redis = (new Database\Redis($config))->connect();
            self::$di->set(
                "redis." . $name,
                $redis
            );
        }
        $redis = self::$di->get("redis." . $name);
        $redis->select($database);
        return $redis;
    }

    /**
     * clock
     * @param null $message
     * @return Clockwork|null
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function clock($message = null): ?Clockwork
    {
        if (!self::config('use')->get('clock')) {
            return null;
        }
        if (!self::$di->has("clock")) {
            $clockwork = new Clockwork();
            $clockwork->storage(new FileStorage(App::$dataPath . '/clockwork'));
            $clockwork->authenticator(new NullAuthenticator);
            $clockwork->log();
            self::$di->set("clock", $clockwork);
            return $clockwork;
        }
        $clock = self::$di->get("clock");
        if (isset($message)) {
            $clock->log('debug', $message);
        }
        return $clock;
    }

    /**
     * scheduler
     * @return Scheduler
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function scheduler(): Scheduler
    {
        if (!self::$di->has("scheduler")) {
            self::$di->set(
                "scheduler",
                new Scheduler()
            );
        }
        return self::$di->get("scheduler");
    }

    /**
     * notify
     * @return Notify
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function notify(string $type): Notify
    {
        if (!self::$di->has("notify." . $type)) {
            self::$di->set(
                "notify." . $type,
                new Notify()
            );
        }
        return self::$di->get("notify." . $type);
    }

    /**
     * Auth
     * @param string $app
     * @return Notify
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function auth(string $app = ""): Notify
    {
        if (!self::$di->has("auth.$app")) {
            self::$di->set(
                "auth.$app",
                new AuthService($app)
            );
        }
        return self::$di->get("auth.$app");
    }

    /**
     * translator
     * @param string|null $lang
     * @return Translator
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function trans(?string $lang = ""): Translator {
        if (!$lang) {
            $lang = self::$di->get('language') ?: 'en';
        }
        if (!self::$di->has("trans")) {
            $translator = new Translator($lang);
            $translator->addLoader('array', new YamlFileLoader());
            $translator->addResource('array', __DIR__ . '/Translator/Lang/en.yaml', 'en');
            $translator->addResource('array', __DIR__ . '/Translator/Lang/zh.yaml', 'zh');
            self::$di->set(
                "trans",
                $translator
            );
        }
        return self::$di->get("trans");
    }
}