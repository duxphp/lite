<?php

declare(strict_types=1);

namespace Dux\Resources\Action;

use Dux\Validator\Data;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

abstract class Resources
{

    protected string $key = "id";
    protected string $label = "";
    protected string $model;
    protected bool $tree = false;
    protected array $pagination = [
        'status' => true,
        'limit' => 20,
    ];

    /**
     * 多条数据允许字段
     * @var array
     */
    public array $includesMany = [];

    /**
     * 多条数据排除字段
     * @var array
     */
    public array $excludesMany = [];

    /**
     * 单条数据允许字段
     * @var array
     */
    public array $includesOne = [];

    /**
     * 单条数据排除字段
     * @var array
     */
    public array $excludesOne = [];


    /**
     * @var callable[]
     */
    public array $createHook = [];

    /**
     * @var callable[]
     */
    public array $editHook = [];

    /**
     * @var callable[]
     */
    public array $storeHook = [];

    /**
     * @var callable[]
     */
    public array $delHook = [];

    /**
     * @var array
     */
    public array $trashedHook = [];
    /**
     * @var array
     */
    public array $restoreHook = [];


    use Many, One, Create, Edit, Store, Delete, Trash, Restore;

    /**
     * 初始化
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return void
     */
    public function init(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
    }

    /**
     * 数据转换
     * 转换数据字段内容
     * @param object $item
     * @return array
     */
    abstract function transform(object $item): array;


    /**
     * 单条或多条数据查询
     * @param Builder $query
     * @return void
     */
    public function query(Builder $query)
    {
    }

    /**
     * 多条数据查询
     * @param Builder $query
     * @param array $args
     * @param ServerRequestInterface $request
     * @return void
     */
    public function queryMany(Builder $query, ServerRequestInterface $request, array $args)
    {
    }

    /**
     * 单条数据查询
     * @param Builder $query
     * @param ServerRequestInterface $request
     * @param array $args
     * @return void
     */
    public function queryOne(Builder $query, ServerRequestInterface $request, array $args)
    {
    }

    /**
     * 多条元数据
     * @param Builder|LengthAwarePaginator $query
     * @param array $data
     * @param ServerRequestInterface $request
     * @param array $args
     * @return array
     */
    public function metaMany(Builder|LengthAwarePaginator $query, array $data, ServerRequestInterface $request, array $args): array
    {
        return [];
    }

    /**
     * 单条元数据
     * @param mixed $data
     * @param ServerRequestInterface $request
     * @param array $args
     * @return array
     */
    public function metaOne(mixed $data, ServerRequestInterface $request, array $args): array
    {
        return [];
    }

    /**
     * 数据保存验证
     * @param array $data
     * @param ServerRequestInterface $request
     * @param array $args
     * @return array
     */
    public function validator(array $data, ServerRequestInterface $request, array $args): array
    {
        return [];
    }

    /**
     * 数据入库格式化
     * @param Data $data
     * @param ServerRequestInterface $request
     * @param array $args
     * @return array
     */
    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        return $data->toArray();
    }

    /**
     * @param array $rule
     * @param Data $data
     * @return array
     */
    public function formatData(array $rule, Data $data): array
    {
        return array_map(function ($item, $key) use ($data) {
            return is_callable($item) ? $item($item, $data) : $item;
        }, $rule);
    }


    /**
     * @param Collection|LengthAwarePaginator|Model|null $data
     * @param callable $callback
     * @return array
     */
    public function transformData(Collection|LengthAwarePaginator|Model|null $data, callable $callback): array
    {
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
            return [
                'data' => $callback($data),
                'meta' => []
            ];
        }
        if (!$data) {
            $data = collect();
        }

        $list = $data->map($callback)->filter()->values();
        $result = [
            'data' => $list->toArray(),
            'meta' => []
        ];

        if ($pageStatus) {
            $result['meta'] = [
                'total' => $total,
                'page' => $page
            ];
        }

        return $result;
    }

    public function translation(ServerRequestInterface $request, string $action): string
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $app = $route->getArgument("app");
        $name = $route->getName();
        return __("message.$action", [
            "%name%" => __("$name.name", $app),
        ], "common");
    }

}