<?php
declare(strict_types=1);

namespace Dux;


use Carbon\Carbon;
use Dux\App\Attribute;
use Dux\Cache\Cache;
use Dux\Command\Command;
use Dux\Config\Config;
use Dux\Database\DbListener;
use Dux\Database\ListCommand;
use Dux\Database\MigrateCommand;
use Dux\Event\Event;
use Dux\Event\EventCommand;
use Dux\Helpers\AppCommand;
use Dux\Helpers\CtrCommand;
use Dux\Helpers\ManageCommand;
use Dux\Helpers\ModelCommand;
use Dux\Permission\PermissionCommand;
use Dux\Queue\QueueCommand;
use Dux\Route\RouteCommand;
use DI\Container;
use Dux\Server\SwowCommand;
use Dux\Server\WorkermanCommand;
use Dux\View\View;
use Dux\Websocket\WebsocketCommand;
use Illuminate\Pagination\Paginator;
use Latte\Engine;
use Phpfastcache\Helper\Psr16Adapter;
use Slim\App as slimApp;
use Slim\Factory\AppFactory;
use Dux\Handlers\ErrorHandler;
use Slim\Psr7\Request;
use \Symfony\Component\Console\Application;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpNotFoundException;

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

    public function loadFunc()
    {
        require_once "Func/Response.php";
        require_once "Func/Common.php";
    }

    /**
     * loadWeb
     * @return void
     */
    public function loadWeb(Container $di): void
    {
        AppFactory::setContainer($di);
        $this->di = $di;
        $this->web = AppFactory::create();
        $this->route = new \Dux\Route\Register();
    }

    /**
     * loadConfig
     * @return void
     */
    /**
     * loadConfig
     * @return void
     */
    public function loadConfig(): void
    {
        $this->debug = (bool)App::config("app")->get("app.debug");
        $this->exceptionTitle = App::config("app")->get("exception.title", $this->exceptionTitle);
        $this->exceptionDesc = App::config("app")->get("exception.desc", $this->exceptionDesc);
        $this->exceptionBack = App::config("app")->get("exception.back", $this->exceptionBack);

        Config::setValues([
            'base_path' => App::$basePath,
            'app_path' => App::$appPath,
            'data_path' => App::$dataPath,
            'config_path' => App::$configPath,
            'public_path' => App::$publicPath,
            'domain' => App::config("app")->get("app.domain"),
        ]);

        $timezone = App::config("app")->get("app.timezone", 'PRC');

        date_default_timezone_set($timezone);
    }

    /**
     * loadCache
     * @return void
     */
    public function loadCache(): void
    {
        $type = App::config("cache")->get("type");
        $this->cache = Cache::init($type, (array)App::config("cache")->get("drivers." . $type));
    }

    /**
     * loadCommand
     * @return void
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
        $commands[] = ManageCommand::class;
        $commands[] = CtrCommand::class;
        $commands[] = \Dux\App\AppCommand::class;
        $commands[] = \Dux\App\AppInstallCommand::class;
        $commands[] = \Dux\App\AppUninstallCommand::class;
        $commands[] = PermissionCommand::class;
        $commands[] = ListCommand::class;
        $commands[] = WorkermanCommand::class;
        $commands[] = SwowCommand::class;
        $commands[] = WebsocketCommand::class;
        $this->command = Command::init($commands);

        // ??????????????????
        App::dbMigrate()->registerAttribute();
    }

    /**
     * loadView
     * @return void
     */
    public function loadView()
    {
        $this->view = View::init("app");
    }

    /**
     * loadRoute
     * @return void
     */
    public function loadRoute(): void
    {

        // ???????????????
        if (!in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) && !headers_sent()) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: *');
            header('Access-Control-Allow-Headers: *');
        }

        // ????????????
        $this->web->addBodyParsingMiddleware();
        // ?????????????????????
        $this->web->addRoutingMiddleware();

        // ??????????????????
        $errorMiddleware = $this->web->addErrorMiddleware($this->debug, true, true);
        $errorHandler = new ErrorHandler($this->web->getCallableResolver(), $this->web->getResponseFactory());
        $errorMiddleware->setDefaultErrorHandler($errorHandler);
        $errorHandler->registerErrorRenderer("application/json", \Dux\Handlers\ErrorJsonRenderer::class);
        $errorHandler->registerErrorRenderer("application/xml", \Dux\Handlers\ErrorXmlRenderer::class);
        $errorHandler->registerErrorRenderer("text/xml", \Dux\Handlers\ErrorXmlRenderer::class);
        $errorHandler->registerErrorRenderer("text/html", \Dux\Handlers\ErrorHtmlRenderer::class);
        $errorHandler->registerErrorRenderer("text/plain", \Dux\Handlers\ErrorPlainRenderer::class);

        // ????????????
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
            return $response->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', '*')
                ->withHeader('Access-Control-Allow-Headers', '*')
                ->withHeader('Access-Control-Expose-Methods', '*')
                ->withHeader('Access-Control-Allow-Credentials', 'true');
        });

    }

    public function loadEvent(): void
    {
        $this->event = new Event();

        // ??????????????????
        $this->event->addListener('server.start', [new DbListener(), 'start']);
    }

    public function loadDb(): void
    {
        App::db();
    }

    /**
     * ????????????
     * @return void
     */
    public function loadApp(): void
    {

        $appList = App::config("app")->get("registers", []);
        foreach ($appList as $vo) {
            App::$registerApp[] = $vo;
        }

        // ????????????
        App::di()->set("attributes", Attribute::load(App::$registerApp));

        // ??????????????????
        $this->event->registerAttribute();

        // ????????????
        foreach ($appList as $vo) {
            call_user_func([new $vo, "init"], $this);
        }
        // ????????????
        foreach ($appList as $vo) {
            call_user_func([new $vo, "register"], $this);
        }

        // ??????????????????
        $this->route->registerAttribute($this);

        // ??????????????????
        foreach ($this->route->app as $route) {
            $route->run($this->web);
        }

        // ????????????
        $this->web->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
            throw new HttpNotFoundException($request);
        });

        // ????????????
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
            $this->menu = new \Dux\Menu\Register();
        }
        return $this->menu;
    }

    public function getPermission(): Permission\Register
    {
        if (!$this->permission) {
            $this->permission = new \Dux\Permission\Register();
        }
        return $this->permission;
    }

}