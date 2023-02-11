<?php
declare(strict_types=1);

namespace Dux\Event;

use Dux\App;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Event extends EventDispatcher {

    public array $registers = [];

    public function addListener(string $eventName, callable|array $listener, int $priority = 0): void
    {
        $this->registers[$eventName][] = !is_array($listener) ? 'callable': implode(':', [$listener[0]::class, $listener[1]]);
        parent::addListener($eventName, $listener, $priority);
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

}