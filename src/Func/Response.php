<?php

use Psr\Http\Message\ResponseInterface;

/**
 * @param ResponseInterface $response
 * @param string $message
 * @param array $data
 * @param int $code
 * @return ResponseInterface
 */
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


/**
 * @param string $message
 * @param int $code
 * @return mixed
 * @throws Exception
 */
function error(string $message, int $code = 200): mixed {
    throw new Exception($message, $code);
}