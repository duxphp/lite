<?php
declare(strict_types=1);

namespace Dux\Event;

use Dux\App;
use JBZoo\Event\EventManager;

class Event {

    public static function registerAttribute(EventManager $event): void {
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
                [$class, $method] = explode(":", $class);
                call_user_func([$event, $params == "on" ? "on" : "once"], [$class, $method]);
            }
        }
    }

}