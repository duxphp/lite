<?php
declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
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
    $response->getBody()->write($payload);
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($code);
}

/**
 * @param string $name
 * @param array $params
 * @return string
 */
function url(string $name, array $params): string {
    return \Dux\App::app()->getRouteCollector()->getRouteParser()->urlFor($name, $params);
}

/**
 * @param Collection|LengthAwarePaginator $data
 * @param callable $callback
 * @return array
 */
function transform(Collection|LengthAwarePaginator $data, callable $callback): array {
    $pageStatus = false;
    $page = 1;
    $total = 0;
    if ($data instanceof LengthAwarePaginator) {
        $pageStatus = true;
        $data->currentPage();
        $data->total();
        $data = $data->getCollection();
    }

    if (!isset($data[0])) {
        return $callback($data);
    }

    $list = $data->map($callback);
    $result = [
        'list' => $list->toArray(),
    ];

    if ($pageStatus) {
        $result['total'] = $total;
        $result['page'] = $page;
    }

    return $result;
}