<?php
declare(strict_types=1);

namespace Dux\Event\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Event {

    public function __construct(string $name, string $type = "on") {
    }
}