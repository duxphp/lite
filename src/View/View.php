<?php
declare(strict_types=1);

namespace Dux\View;

use Dux\App;
use Twig\Environment;

class View {

    static function init(string $name, string $path): Environment {
        $loader = new \Twig\Loader\FilesystemLoader($path);
        return new \Twig\Environment($loader, [
            'cache' => App::$dataPath . '/cache.' . $name,
        ]);
    }
}