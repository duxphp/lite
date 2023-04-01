<?php
declare(strict_types=1);

namespace Dux\Notify;

use Psr\Http\Message\ResponseInterface;

class NotifyResponse
{
    public function consume(ResponseInterface $response, string $topic): ResponseInterface
    {
        return $response->withHeader('notify', $topic);
    }
}