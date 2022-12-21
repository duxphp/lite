<?php
declare(strict_types=1);

namespace Dux;


use Dux\Cache\Cache;
use Dux\Command\Command;
use Dux\Database\MigrateCommand;
use Dux\Event\EventCommand;
use Dux\Helpers\AppCommand;
use Dux\Queue\QueueCommand;
use Dux\Route\Register;
use Dux\Route\RouteCommand;
use DI\Container;
use Dux\View\View;
use Evenement\EventEmitter;
use Latte\Engine;
use Noodlehaus\Config;
use Phpfastcache\Helper\Psr16Adapter;
use Slim\App as slimApp;
use Slim\Factory\AppFactory;
use Dux\Handlers\ErrorHandler;
use \Symfony\Component\Console\Application;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpNotFoundException;

class Bootstrap {

    public bool $debug = true;
    public ?slimApp $web = null;
    public ?Application $command = null;
    public Psr16Adapter $cache;
    public array $config;
    public string $exceptionTitle = "Application Error";
    public string $exceptionDesc = "A website error has occurred. Sorry for the temporary inconvenience.";
    public string $exceptionBack = "go back";
    public Engine $view;
    public Register $route;

    public EventEmitter $event;

    /**
     * init
     */
    public function __construct() {
        error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
    }

    public function loadFunc() {
        require_once "Func/Response.php";
    }

    /**
     * loadWeb
     * @return void
     */
    public function loadWeb(Container $di): void {
        AppFactory::setContainer($di);
        $this->web = AppFactory::create();
        $this->route = new Register();
    }

    /**
     * loadConfig
     * @return void
     */
    /**
     * loadConfig
     * @return void
     */
    public function loadConfig(): void {
        $this->debug = (bool)App::config("app")->get("app.debug");
        $this->exceptionTitle = App::config("app")->get("exception.title", $this->exceptionTitle);
        $this->exceptionDesc = App::config("app")->get("exception.desc", $this->exceptionDesc);
        $this->exceptionBack = App::config("app")->get("exception.back", $this->exceptionBack);
    }

    /**
     * loadCache
     * @return void
     */
    public function loadCache(): void {
        $type = App::config("cache")->get("type");
        $this->cache = Cache::init($type, (array)App::config("cache")->get("drivers." . $type));
    }

    /**
     * loadCommand
     * @return void
     */
    public function loadCommand(): void {
        $commands = App::config("command")->get("registers", []);
        $commands[] = QueueCommand::class;
        $commands[] = RouteCommand::class;
        $commands[] = MigrateCommand::class;
        $commands[] = EventCommand::class;
        $commands[] = AppCommand::class;
        $this->command = Command::init($commands);
    }

    /**
     * loadView
     * @return void
     */
    public function loadView() {
        $this->view = View::init("app");
    }

    /**
     * loadRoute
     * @return void
     */
    public function loadRoute(): void {
        // 初始化中件
        $this->web->addBodyParsingMiddleware();
        $this->web->addRoutingMiddleware();
        // 注册异常处理
        $errorMiddleware = $this->web->addErrorMiddleware($this->debug, true, true);
        $errorHandler = new ErrorHandler($this->web->getCallableResolver(), $this->web->getResponseFactory());
        $errorMiddleware->setDefaultErrorHandler($errorHandler);
        $errorHandler->registerErrorRenderer("application/json", \Dux\Handlers\ErrorJsonRenderer::class);
        $errorHandler->registerErrorRenderer("application/xml", \Dux\Handlers\ErrorXmlRenderer::class);
        $errorHandler->registerErrorRenderer("text/xml", \Dux\Handlers\ErrorXmlRenderer::class);
        $errorHandler->registerErrorRenderer("text/html", \Dux\Handlers\ErrorHtmlRenderer::class);
        $errorHandler->registerErrorRenderer("text/plain", \Dux\Handlers\ErrorPlainRenderer::class);

        $this->web->options('/{routes:.+}', function ($request, $response, $args) {
            return $response;
        });
        $this->web->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $response = $handler->handle($request);
            return $response->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, X-CSRF-Token, AccessKey, X-Dux-Platform, Content-MD5, Content-Date')
                ->withHeader('Access-Control-Expose-Methods', '*');
        });
    }

    public function loadEvent(): void {
        $this->event = new EventEmitter();
    }

    /**
     * 载入应用
     * @return void
     */
    public function loadApp(): void {

        $appList = App::config("app")->get("registers", []);
        foreach ($appList as $vo) {
            App::$registerApp[] = $vo;
        }
        // 事件注册
        foreach ($appList as $vo) {
            call_user_func([new $vo, "event"], $this->event);
        }
        // 应用注册
        foreach ($appList as $vo) {
            call_user_func([new $vo, "register"], $this);
        }
        // 模型注册
        foreach ($appList as $vo) {
            call_user_func([new $vo, "model"], App::dbMigrate(), App::db());
        }
        // 应用路由
        foreach ($appList as $vo) {
            call_user_func([new $vo, "appRoute"], $this->route);
        }
        // 普通路由
        foreach ($appList as $vo) {
            call_user_func([new $vo, "route"], $this->route);
        }
        // 路由注册
        foreach ($this->route->app as $route) {
            $route->run($this->web);
        }
        // 公共路由
        $this->web->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
            throw new HttpNotFoundException($request);
        });
        // 应用启动
        foreach ($appList as $vo) {
            call_user_func([new $vo, "boot"], $this);
        }
    }

    public function run(): void {
        if ($this->command) {
            $this->command->run();
        } else {
            $this->web->run();
        }
    }

}