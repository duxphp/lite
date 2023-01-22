<?php
declare(strict_types=1);
namespace Dux\Handlers;

use Throwable;

trait ErrorRendererTrait
{

    protected function getErrorTitle(Throwable $exception): string {
        $defaultTitle = parent::getErrorTitle($exception);
        if ($exception instanceof Exception) {
            $defaultTitle = $exception->getMessage();
        }
        return $defaultTitle;
    }

    protected function getErrorDescription(Throwable $exception): string {
        $defaultDesc = parent::getErrorDescription($exception);
        if ($exception instanceof ExceptionValidator) {
            $defaultDesc = $exception->getDescription();
        }
        return $defaultDesc;
    }
}