<?php
declare(strict_types=1);

namespace Dux\View;

use Dux\App;
use Latte\Engine;

class View {

    static function init(string $name): Engine {
        $latte = new Engine;
        $latte->setTempDirectory(App::$dataPath . '/tpl/' . $name);
        return $latte;
    }
}