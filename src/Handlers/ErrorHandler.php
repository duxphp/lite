<?php
declare(strict_types=1);
namespace Dux\Handlers;

use Dux\App;
use Slim\Handlers\ErrorHandler as slimErrorHandler;

class ErrorHandler extends slimErrorHandler
{
    protected function logError(string $error): void
    {
        if ($this->statusCode !== 404) {
            App::log()->error($error);
        }
    }
}