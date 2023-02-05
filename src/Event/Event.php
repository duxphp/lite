<?php
declare(strict_types=1);

namespace Dux\Event;

use Dux\App;
use JBZoo\Event\EventManager;

class Event {

    public array $registers = [];
    private EventManager $event;

    public function __construct() {
        $this->event = new EventManager();
    }

    public function on($name, string|callable $callback, int $priority = EventManager::MID): void {
        $this->register($name, $callback, $priority);
    }

    public function once($name, string|callable $callback, int $priority = EventManager::MID): void {
        $this->register($name, $callback, $priority, true);
    }

    public function trigger(string $eventName, array $arguments = [], ?callable $continueCallback = null): int {
        return $this->event->trigger($eventName, $arguments, $continueCallback);
    }

    private function register($name, string|callable $callback, int $priority = EventManager::MID, bool $one = false): void {
        if (is_callable($callback)) {
            call_user_func([$this->event, $one ? "once" : "on"], $name, $callback, $priority);
            return;
        }
        call_user_func([$this->event, $one ? "once" : "on"], $name, function () use ($callback) {
            $params = func_get_args();
            [$class, $method] = explode(":", $callback, 2);
            $call = new $class();
            if (!$method) {
                $call();
            } else {
                $call->$method($params);
            }
        }, $priority);
    }

    public function registerAttribute(): void {
        $attributes = (array)App::di()->get("attributes");
        foreach ($attributes as $attribute => $list) {
            if (
                $attribute != \Dux\Event\Attribute\Event::class
            ) {
                continue;
            }
            foreach ($list as $vo) {
                $class = $vo["class"];
                $params = $vo["params"];
                if (!$params["name"]) {
                    throw new \Exception("method [$class] The annotation is missing the name parameter");
                }
                $this->registers[$params["name"]][] = $class;
                call_user_func([$this, $params["type"] == "on" ? "on" : "once"], $params["name"], $class, $params["priority"] ?: EventManager::MID);
            }
        }
    }

    public function __call($name, $arguments) {
        return call_user_func_array([$this->event, $name], $arguments);
    }

}