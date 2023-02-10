<?php
declare(strict_types=1);

namespace Dux\Event;

use Dux\App;
use JBZoo\Event\EventManager;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Event {

    public array $registers = [];
    private EventDispatcher $event;

    public function __construct() {
        $this->event = new EventDispatcher();

    }

    public function addListener(string $eventName, callable|array $listener, int $priority = 0): void
    {
        $this->registers[$eventName][] = is_callable($listener) ? 'callable': implode(':', $listener);
        $this->event->addListener($eventName, $listener, $priority);
    }

    public function registerAttribute(): void {
        $attributes = (array)App::di()->get("attributes");
        foreach ($attributes as $attribute => $list) {
            if (
                $attribute !== \Dux\Event\Attribute\Listener::class
            ) {
                continue;
            }
            foreach ($list as $vo) {
                [$class, $method] = explode(':', $vo["class"]);
                $params = $vo["params"];
                if (!$params["name"]) {
                    throw new \RuntimeException("method [$class:$method] The annotation is missing the name parameter");
                }
                $this->addListener($params["name"], [new $class, $method], (int) $params["priority"] ?: 0);
            }
        }
    }

    public function __call($name, $arguments): EventDispatcher {
        return $this->event->{$name}(...$arguments);
    }

}