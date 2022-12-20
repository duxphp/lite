<?php
declare(strict_types=1);

namespace Dux;


use Dux\Cache\Cache;
use Dux\Command\Command;
use Dux\Queue\QueueCommand;
use Dux\Route\RouteCommand;
use DI\Container;
use Dux\View\View;
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
    public Container $container;
    public ?slimApp $web = null;
    public ?Application $command = null;
    public Psr16Adapter $cache;
    public array $config;
    public string $exceptionTitle = "Application Error";
    public string $exceptionDesc = "A website error has occurred. Sorry for the temporary inconvenience.";
    public string $exceptionBack = "go back";
    public \Twig\Environment $view;

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
    /**
     * loadConfig
     * @return void
     */
    public function loadConfig(): void {
        foreach (glob(App::$configPath . "/*.yaml") as $vo) {
            $path = pathinfo($vo);
            $this->config[$path['filename']] = new Config($vo);
        }
        $this->debug = (bool)$this->config["app"]->get("app.debug");
        $this->exceptionTitle = $this->config["app"]->get("exception.title", $this->exceptionTitle);
        $this->exceptionDesc = $this->config["app"]->get("exception.desc", $this->exceptionDesc);
        $this->exceptionBack = $this->config["app"]->get("exception.back", $this->exceptionBack);
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
     * loadView
     * @return void
     */
    public function loadView() {
        $this->view = View::init("app", __DIR__ . "/Tpl");
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
            $this->web->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
                throw new HttpNotFoundException($request);
            });
            $this->web->run();
        }
    }

}