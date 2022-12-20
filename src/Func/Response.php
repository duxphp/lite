<?php

use Psr\Http\Message\ResponseInterface;

function send(ResponseInterface $response, string $message, array $data = [], int $code = 200): ResponseInterface {
    $resfult = [];
    $resfult["code"] = $code;
    $resfult["message"] = $message;
    $resfult["data"] = $data;
    $payload = json_encode($resfult, JSON_PRETTY_PRINT);
    $response->withHeader('Content-Type', 'application/json')->withStatus($code);
    $response->getBody()->write($payload);
    return $response;
}