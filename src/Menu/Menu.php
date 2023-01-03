<?php
declare(strict_types=1);

namespace Dux\Menu;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteCollectorProxy;

class Menu {

    private array $data;
    private array $push;
    private string $pattern;


    public function __construct(string $pattern = "") {
        $this->pattern = $pattern;
    }

    public function add(string $app, array $config): MenuApp {
        $config["app"] = $app;
        $menuApp = new MenuApp($config, $this->pattern);
        $this->data[$app] = $menuApp;
        return $menuApp;
    }

    public function push(string $app): MenuApp {
        $menuApp = new MenuApp();
        $this->push[$app][] = $menuApp;
        return $menuApp;
    }

    public function get() {
        $menuData = [];
        foreach ($this->data as $name => $app) {
            $appData = $app->get();
            if ($this->push[$name]) {
                foreach ($this->push[$name] as $push) {
                    $appData["children"] = [...$appData["children"], ...$push["children"]];
                }
            }
            $menuData[] = $appData;
        }
        $restData = [];
        foreach ($menuData as $appData) {
            $groupsMenu = [];
            foreach ($appData["children"] as $groupData) {
                $groupData["children"] = collect($groupData["children"])->sortBy('order')->toArray();
                $groupsMenu[] = $groupData;
            }
            $appData["children"] = collect($groupsMenu)->sortBy('order')->toArray();
            $restData[] = $appData;
        }
        return collect($restData)->sortBy('order')->toArray();

    }

}