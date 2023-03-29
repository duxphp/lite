<?php
declare(strict_types=1);

namespace Dux;


use Clockwork\Support\Slim\ClockworkMiddleware;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Dux\App\AppInstallCommand;
use Dux\App\AppUninstallCommand;
use Dux\App\Attribute;
use Dux\Cache\Cache;
use Dux\Command\Command;
use Dux\Config\Config;
use Dux\Database\ListCommand;
use Dux\Database\MigrateCommand;
use Dux\Database\ProxyCommand;
use Dux\Event\Event;
use Dux\Event\EventCommand;
use Dux\Handlers\ErrorHandler;
use Dux\Handlers\ErrorHtmlRenderer;
use Dux\Handlers\ErrorJsonRenderer;
use Dux\Handlers\ErrorPlainRenderer;
use Dux\Handlers\ErrorXmlRenderer;
use Dux\Helpers\AppCommand;
use Dux\Helpers\CtrCommand;
use Dux\Helpers\ManageCommand;
use Dux\Helpers\ModelCommand;
use Dux\Permission\PermissionCommand;
use Dux\Permission\Register;
use Dux\Queue\QueueCommand;
use Dux\Route\RouteCommand;
use Dux\Scheduler\SchedulerCommand;
use Dux\server\WebCommand;
use Dux\View\View;
use Dux\Websocket\WebsocketCommand;
use Illuminate\Pagination\Paginator;
use Latte\Engine;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App as slimApp;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Symfony\Component\Console\Application;

class Bootstrap
{

    public bool $debug = true;
    public ?slimApp $web = null;
    public ?Application $command = null;
    public Psr16Adapter $cache;
    public array $config;
    public string $exceptionTitle = "Application Error";
    public string $exceptionDesc = "A website error has occurred. Sorry for the temporary inconvenience.";
    public string $exceptionBack = "go back";
    public Engine $view;

    public Event $event;
    public Route\Register $route;
    public ?Menu\Register $menu = null;
    public ?Permission\Register $permission = null;
    private Container $di;

    /**
     * init
     */
    public function __construct()
    {
        error_reporting(E_ALL ^ E_DEPRECATED ^ E_WARNING);
    }

    public function loadFunc(): void
    {
        require_once "Func/Response.php";
        require_once "Func/Common.php";
    }

    /**
     * loadWeb
     * @param Container $di
     * @return void
     */
    public function loadWeb(Container $di): void
    {
        AppFactory::setContainer($di);
        $this->di = $di;
        $this->web = AppFactory::create();
        $this->route = new Route\Register();
    }

    /**
     * loadConfig
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function loadConfig(): void
    {

        $this->debug = (bool)App::config("use")->get("app.debug");
        $this->exceptionTitle = App::config("use")->get("exception.title", $this->exceptionTitle);
        $this->exceptionDesc = App::config("use")->get("exception.desc", $this->exceptionDesc);
        $this->exceptionBack = App::config("use")->get("exception.back", $this->exceptionBack);


        Config::setValues([
            'base_path' => App::$basePath,
            'app_path' => App::$appPath,
            'data_path' => App::$dataPath,
            'config_path' => App::$configPath,
            'public_path' => App::$publicPath,
            'domain' => App::config("use")->get("app.domain"),
        ]);

        $timezone = App::config("use")->get("app.timezone", 'PRC');
        date_default_timezone_set($timezone);

    }

    /**
     * loadCache
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function loadCache(): void
    {
        $type = App::config("cache")->get("type");
        $this->cache = Cache::init($type, (array)App::config("cache")->get("drivers." . $type));
    }

    /**
     * loadCommand
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function loadCommand(): void
    {
        $commands = App::config("command")->get("registers", []);
        $commands[] = QueueCommand::class;
        $commands[] = RouteCommand::class;
        $commands[] = MigrateCommand::class;
        $commands[] = EventCommand::class;
        $commands[] = AppCommand::class;
        $commands[] = ModelCommand::class;
        $commands[] = ProxyCommand::class;
        $commands[] = ManageCommand::class;
        $commands[] = CtrCommand::class;
        $commands[] = App\AppCommand::class;
        $commands[] = AppInstallCommand::class;
        $commands[] = AppUninstallCommand::class;
        $commands[] = PermissionCommand::class;
        $commands[] = ListCommand::class;
        $commands[] = WebsocketCommand::class;
        $commands[] = SchedulerCommand::class;
        $commands[] = WebCommand::class;
        $this->command = Command::init($commands);

        // 注册模型迁移
        App::dbMigrate()->registerAttribute();
    }

    /**
     * loadView
     * @return void
     */
    public function loadView(): void
    {
        $this->view = View::init("app");
    }

    /**
     * loadRoute
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function loadRoute(): void
    {
        if (App::config('use')->get('clock')) {
            $this->web->add(new ClockworkMiddleware($this->web, $this->di->get('clock')));
        }
        // 解析内容
        $this->web->addBodyParsingMiddleware();
        // 注册路由中间件
        $this->web->addRoutingMiddleware();

        // 注册异常处理
        $errorMiddleware = $this->web->addErrorMiddleware($this->debug, true, true);
        $errorHandler = new ErrorHandler($this->web->getCallableResolver(), $this->web->getResponseFactory());
        $errorMiddleware->setDefaultErrorHandler($errorHandler);
        $errorHandler->registerErrorRenderer("application/json", ErrorJsonRenderer::class);
        $errorHandler->registerErrorRenderer("application/xml", ErrorXmlRenderer::class);
        $errorHandler->registerErrorRenderer("text/xml", ErrorXmlRenderer::class);
        $errorHandler->registerErrorRenderer("text/html", ErrorHtmlRenderer::class);
        $errorHandler->registerErrorRenderer("text/plain", ErrorPlainRenderer::class);

        // 跨域处理
        $this->web->options('/{routes:.+}', function ($request, $response, $args) {
            return $response;
        });
        $this->web->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $params = $request->getQueryParams();
            Paginator::currentPageResolver(static function ($pageName = 'page') use ($params) {
                $page = $params[$pageName];
                if ((int)$page >= 1) {
                    return $page;
                }
                return 1;
            });
            $response = $handler->handle($request);

            $origin = $request->getHeaderLine('Origin');
            return $response->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Methods', '*')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Content-MD5, Platform, Content-Date, Authorization, AccessKey')
                ->withHeader('Access-Control-Expose-Methods', '*')
                ->withHeader('Access-Control-Expose-Headers', '*')
                ->withHeader('Access-Control-Allow-Credentials', 'true');
        });

        $cache = (bool)App::config("use")->get("app.cache");
        if ($cache) {
            $routeCollector = $this->web->getRouteCollector();
            $routeCollector->setCacheFile(App::$dataPath . '/cache/route.file');
        }

    }

    public function loadEvent(): void
    {
        $this->event = new Event();
    }

    public function loadDb(): void
    {
    }

    /**
     * load app
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function loadApp(): void
    {

        $appList = App::config("app")->get("registers", []);
        foreach ($appList as $vo) {
            App::$registerApp[] = $vo;
        }

        // 注解加载
        App::di()->set("attributes", Attribute::load(App::$registerApp));

        // 事件注解加载
        $this->event->registerAttribute();

        // 事件注册
        foreach ($appList as $vo) {
            call_user_func([new $vo, "init"], $this);
        }
        // 应用注册
        foreach ($appList as $vo) {
            call_user_func([new $vo, "register"], $this);
        }

        // 注解路由注册
        $this->route->registerAttribute($this);

        // 普通路由注册
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

    public function run(): void
    {
        if ($this->command) {
            $this->command->run();
        } else {
            $this->web->run();
        }
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function getRoute(): Route\Register
    {
        return $this->route;
    }

    public function getMenu(): Menu\Register
    {
        if (!$this->menu) {
            $this->menu = new Menu\Register();
        }
        return $this->menu;
    }

    public function getPermission(): Permission\Register
    {
        if (!$this->permission) {
            $this->permission = new Register();
        }
        return $this->permission;
    }

}