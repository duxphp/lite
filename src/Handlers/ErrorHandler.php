<?php
declare(strict_types=1);
namespace Dux\Handlers;

use Dux\App;
use Slim\Exception\HttpSpecializedException;
use Slim\Handlers\ErrorHandler as slimErrorHandler;

class ErrorHandler extends slimErrorHandler
{



    protected function logError(string $error): void
    {
        if (
            $this->statusCode == 404 ||
            $this->exception instanceof HttpSpecializedException ||
            $this->exception instanceof ExceptionBusiness
        ) {
            return;
        }
        App::log()->error($error);
    }
}