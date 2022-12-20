<?php
declare(strict_types=1);
namespace Dux\Handlers;

use Slim\Error\Renderers\HtmlErrorRenderer;
use Throwable;

class ErrorHtmlRenderer extends HtmlErrorRenderer
{
    protected function getErrorTitle(Throwable $exception): string {
        return parent::getErrorTitle($exception);
    }

}