<?php
declare(strict_types=1);

namespace Dux;


use Dux\Cache\Cache;
use Dux\Command\Command;
use Dux\Queue\QueueCommand;
use Dux\Route\RouteCommand;
use DI\Container;
use Noodlehaus\Config;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App as slimApp;
use Slim\Factory\AppFactory;
use Medoo\Medoo;
use Dux\Handlers\ErrorHandler;
use Dux\Database\Db;
use \Symfony\Component\Console\Application;

class Bootstrap {

    public Container $container;
    public ?slimApp $web = null;
    public ?Application $command = null;
    public Psr16Adapter $cache;
    public array $config;

    /**
     * init
     */
    public function __construct() {
        error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
        $container = new Container();
        $this->container = $container;
    }

    public function loadFunc() {
        require_once "Func/Response.php";
    }

    /**
     * loadWeb
     * @return void
     */
    public function loadWeb(): void {
        AppFactory::setContainer($this->container);
        $this->web = AppFactory::create();
    }

    /**
     * loadConfig
     * @return void
     */
    public function loadConfig(): void {
        foreach (glob(App::$configPath . "/*.yaml") as $vo) {
            $path = pathinfo($vo);
            $this->config[$path['filename']] = new Config($vo);
        }
    }

    /**
     * loadCache
     * @return void
     */
    public function loadCache(): void {
        $type = $this->config["cache"]->get("type");
        $this->cache = Cache::init($type, (array)$this->config["cache"]->get("drivers." . $type));
    }

    /**
     * loadCommand
     * @return void
     */
    public function loadCommand(): void {
        $commands = $this->config["command"]->get("registers", []);
        $commands[] = QueueCommand::class;
        $commands[] = RouteCommand::class;
        $this->command = Command::init($commands);
    }

    /**
     * loadRoute
     * @return void
     */
    public function loadRoute(): void {
        // 初始化中件
        $this->web->addRoutingMiddleware();
        // 注册异常处理
        $errorMiddleware = $this->web->addErrorMiddleware(true, true, true);
        $errorMiddleware->setDefaultErrorHandler(new ErrorHandler($this->web->getCallableResolver(), $this->web->getResponseFactory()));


//        $this->web->get('/', function (Request $request, Response $response) {
//            $response->getBody()->write('<a href="/hello/world">Try /hello/world</a>');
//            return $response;
//        });
//        $this->web->get('/hello/{name}', function (Request $request, Response $response, $args) {
//            $name = $args['name'];
//            $response->getBody()->write("Hello, $name");
//            return $response;
//        });
    }

    /**
     * 载入应用
     * @return void
     */
    public function loadApp(): void {

        $appList = $this->config["app"]->get("registers", []);
        foreach ($appList as $vo) {
            App::$registerApp[] = $vo;
        }

        $list = [];
        $beforeList = [];
        $afterList = [];
        foreach (App::$registerApp as $vo) {
            if (method_exists($vo, "register")) {
                $list[] = $vo;
            }
            if (method_exists($vo, "boot")) {
                $beforeList[] = $vo;
            }
            if (method_exists($vo, "after")) {
                $afterList[] = $vo;
            }
        }
        foreach ($beforeList as $vo) {
            call_user_func([new $vo, "register"], [$this->web, $this->container]);
        }
        foreach ($list as $vo) {
            call_user_func([new $vo, "boot"], [$this->web, $this->container]);
        }
        foreach ($afterList as $vo) {
            call_user_func([new $vo, "after"], [$this->web, $this->container]);
        }
    }

    public function run(): void {
        $this->loadApp();
        if ($this->command) {
            $this->command->run();
        } else {
            foreach (App::$registerRoute as $route) {
                $route->run($this->web);
            }
            $this->web->run();
        }
    }

}