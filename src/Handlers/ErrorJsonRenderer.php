<?php
declare(strict_types=1);
namespace Dux\Handlers;

use Dux\App;
use Slim\Interfaces\ErrorRendererInterface;
use Throwable;

class ErrorJsonRenderer implements ErrorRendererInterface
{
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        return 'My awesome format';
    }
}