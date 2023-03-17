<?php
declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use \Illuminate\Database\Eloquent\Model;
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
 * @param ResponseInterface $response
 * @param string $message
 * @param int $code
 * @return ResponseInterface
 */
function sendText(ResponseInterface $response, string $message, int $code = 200): ResponseInterface {
    $response->getBody()->write($message);
    return $response
        ->withHeader('Content-Type', 'text/html')
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
 * @param Collection|LengthAwarePaginator|Model|null $data
 * @param callable $callback
 * @return array
 */
function format_data(Collection|LengthAwarePaginator|Model|null $data, callable $callback): array {
    $pageStatus = false;
    $page = 1;
    $total = 0;
    if ($data instanceof LengthAwarePaginator) {
        $pageStatus = true;
        $page = $data->currentPage();
        $total = $data->total();
        $data = $data->getCollection();
    }

    if ($data instanceof Model) {
        return $callback($data);
    }
    if (!$data) {
        $data = collect();
    }

    $list = $data->map($callback)->filter()->values();
    $result = [
        'list' => $list->toArray(),
    ];

    if ($pageStatus) {
        $result['total'] = $total;
        $result['page'] = $page;
    }

    return $result;
}