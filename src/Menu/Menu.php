<?php
declare(strict_types=1);

namespace Dux\Menu;

class Menu
{

    private array $data = [];
    private array $push = [];
    private string $pattern = "";


    public function __construct(string $pattern = "")
    {
        $this->pattern = $pattern;
    }

    public function add(string $app, array $config): MenuApp
    {
        $config["app"] = $app;
        $menuApp = new MenuApp($config, $this->pattern);
        $this->data[$app] = $menuApp;
        return $menuApp;
    }

    public function push(string $app): MenuApp
    {
        $menuApp = new MenuApp();
        $this->push[$app][] = $menuApp;
        return $menuApp;
    }

    public function get(array $auth = []): array
    {
        $menuData = [];
        foreach ($this->data as $name => $app) {
            $appData = $app->get();
            if ($auth && $appData["auth"] && !in_array($appData["auth"], $auth)) {
                continue;
            }
            if ($this->push[$name]) {
                foreach ($this->push[$name] as $push) {
                    $object = $push->get();
                    $appData["children"] = [...$appData["children"], ...($object["children"] ?: [])];
                }
            }
            $menuData[] = $appData;
        }
        $restData = [];
        foreach ($menuData as $appData) {
            $groupsMenu = [];
            foreach ($appData["children"] as $groupData) {
                $list = [];
                foreach ($groupData["children"] as $vo) {
                    if ($auth && $vo["auth"] && !in_array($vo["auth"], $auth)) {
                        continue;
                    }
                    $list[] = $vo;
                }
                $list = collect($list)->sortBy('order')->values()->toArray();
                if (!$list) {
                    continue;
                }
                $groupData["children"] = $list;
                $groupsMenu[] = $groupData;
            }
            $groupList = collect($groupsMenu)->sortBy('order')->values()->toArray();
            if (empty($groupList) && !$appData['url']) {
                continue;
            }
            $appData["children"] = $groupList;
            $restData[] = $appData;
        }
        return collect($restData)->sortBy('order')->values()->toArray();

    }

}