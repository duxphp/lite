<?php

function send($response, string $message, array $data = [], int $code = 200) {
    $resfult = [];
    $resfult["code"] = $code;
    $resfult["message"] = $message;
    $resfult["data"] = $data;
    $payload = json_encode($resfult);
    return $response->getBody()->write($payload)->withHeader('Content-Type', 'application/json')->withStatus($code);
}