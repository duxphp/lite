<?php
declare(strict_types=1);

namespace Dux\App;

use Dux\Bootstrap;
use Dux\Database\Migrate;
use Dux\Route\Register as Route;
use Evenement\EventEmitter;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;

class AppExtend {

    public string $name = "";

    public string $description = "";

    /**
     * @param Bootstrap $app
     * @return void
     */
    public function register(Bootstrap $app): void {
    }

    /**
     * @param Bootstrap $app
     * @return void
     */
    public function boot(Bootstrap $app): void {
    }

    /**
     * @param Route $app
     * @return void
     */
    public function appRoute(Route $app): void {
    }

    /**
     * @param Route $app
     * @return void
     */
    public function route(Route $app): void {
    }

    public function model(Migrate $migrate, Manager $db): void {
    }

    public function event(EventEmitter $event): void {
    }

}