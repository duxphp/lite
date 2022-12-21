<?php
declare(strict_types=1);

namespace Dux\App;

use Dux\Bootstrap;
use Dux\Database\MedooExtend;
use Dux\Database\Migrate;
use Dux\Route\Register as Route;
use Evenement\EventEmitter;
use Illuminate\Database\Capsule\Manager as Capsule;

class AppExtend {

    public string $name = "";

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

    public function model(Migrate $migrate, MedooExtend $db): void {
    }

    public function event(EventEmitter $event): void {
    }

}