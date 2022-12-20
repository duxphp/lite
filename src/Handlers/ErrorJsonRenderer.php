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
        $resfult = [
            "code" => $exception->getCode(),
            "message" => $exception->getMessage(),
            "data" => []
        ];

        if ($displayErrorDetails) {
            do {
                $resfult['data'][] = $this->formatExceptionFragment($exception);
            } while ($exception = $exception->getPrevious());
        }
        return (string) json_encode($resfult, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function formatExceptionFragment(Throwable $exception): array
    {
        $code = $exception->getCode();
        return [
            'type' => get_class($exception),
            'code' => $code,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
    }
}