<?php
declare(strict_types=1);

namespace Dux\App;

use Dux\Bootstrap;

class AppExtend
{

    public string $name = "";

    public string $description = "";

    /**
     * @param Bootstrap $app
     * @return void
     */
    public function init(Bootstrap $app): void
    {
    }


    /**
     * @param Bootstrap $app
     * @return void
     */
    public function register(Bootstrap $app): void
    {
    }

    /**
     * @param Bootstrap $app
     * @return void
     */
    public function boot(Bootstrap $app): void
    {
    }

}