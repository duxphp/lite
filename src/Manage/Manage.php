<?php

namespace Dux\Manage;

use Dux\App;
use Dux\Handlers\ExceptionBusiness;
use Dux\Validator\Data;
use Dux\Validator\Validator;
use \Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Collection;

/**
 * @method listWhere(Builder $query, array $args, ServerRequestInterface $request): array
 * @method listFormat(object $item): array
 * @method listAssign($query, $args): array
 * @method infoWhere($query, array $args, ServerRequestInterface $request): array
 * @method infoAssign($info): array
 * @method infoFormat($info): array
 * @method saveValidator(array $args, ServerRequestInterface $request): array
 * @method saveFormat(Data $data, int $id, ServerRequestInterface $request): array
 * @method saveAfter(Data $data, $info)
 * @method storeBefore(array $updateData, int $id, $data)
 * @method storeAfter($info, array $updateData, $data)
 * @method delWhere(Builder $query, array $args): Builder
 * @method delBefore($info, array $args)
 * @method delAfter($info, array $args)
 * @method trashedWhere(Builder $query, array $args): Builder
 * @method trashedBefore($info, array $args)
 * @method trashedAfter($info, array $args)
 * @method restoreWhere(Builder $query, array $args): Builder
 * @method restoreBefore($info, array $args)
 * @method restoreAfter($info, array $args)
 */
class Manage {

    protected string $id = "id";
    protected string $name = "";
    protected string $model = "";
    protected bool $tree = false;
    protected int $listLimit = 20;
    protected array $listFields = [];
    protected bool $listPage = true;

    /**
     * 获取列表
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $queryParams = $request->getQueryParams();
        $limit = $queryParams["limit"] ?: $this->listLimit;
        $treeStatus = $this->tree;
        $pageStatus = $this->listPage;
        /**
         * @var $query Builder
         */
        $query = $this->model::query();

        $key = $queryParams['id'];
        if ($key) {
            $query->where('id', $key);
        }

        if ($this->listFields) {
            $query = $query->select($this->listFields);
        }

        if (method_exists($this, "listWhere")) {
            $query = $this->listWhere($query, $args, $request);
        }
        if ($pageStatus && !$treeStatus) {
            $query = $query->paginate($limit);
        } else {
            $query = $query->get()->toTree();
        }
        $assign = [];
        if (method_exists($this, "listFormat")) {
            $assign = format_data($query, function ($item): array {
                return $this->listFormat($item);
            });
        } else {
            $assign = [
                "list" => $query->toArray()
            ];
        }
        if (method_exists($this, "listAssign")) {
            $assign = [...$assign, ...$this->listAssign($query, $args)];
        }
        return send($response, "ok", $assign);
    }

    /**
     * 获取信息
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function info(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $id = $args["id"] ?: 0;
        $info = collect();
        if ($id) {
            /**
             * @var $query Builder
             */
            $query = $this->model::query();
            if (method_exists($this, "infoWhere")) {
                $info = $this->infoWhere($query, $args, $request)->first();
            } else {
                $info = $query->find($id);
            }
            $data = format_data($info, function ($item): array {
                return method_exists($this, "infoFormat") ? $this->infoFormat($item) : $item;
            });
        } else {
            $data = [];
        }

        if (method_exists($this, "infoAssign")) {
            $data = [...$data, ...$this->infoAssign($info)];
        }
        return send($response, "ok", $data);
    }

    /**
     * 保存数据
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function save(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $id = $args["id"] ?: 0;
        $name = $this->name;
        $data = Validator::parser([...$request->getParsedBody(), ...$args], method_exists($this, "saveValidator") ? $this->saveValidator($args, $request) : []);
        App::db()->getConnection()->beginTransaction();

        if (method_exists($this, "saveFormat")) {
            $modelData = $this->saveFormat($data, $id, $request);
        } else {
            $modelData = (array)$data;
        }

        if ($id) {
            $model = $this->model::find($id);
        } else {
            $model = new $this->model;
        }
        foreach ($modelData as $key => $vo) {
            $model->$key = $vo;
        }

        if (method_exists($this, "saveBefore")) {
            $this->saveBefore($data, $model, $id);
        }
        $model->save();
        if (method_exists($this, "saveAfter")) {
            $this->saveAfter($data, $model, $id);
        }
        App::db()->getConnection()->commit();
        return send($response, ($id ? "编辑" : "添加") . "{$name}成功");
    }

    /**
     * 保存单个数据
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function store(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $id = $args["id"] ?: 0;
        $data = $request->getParsedBody();
        App::db()->getConnection()->beginTransaction();
        $updateData = [
            $data["key"] => $data["value"]
        ];
        if (method_exists($this, "storeBefore")) {
            $updateData = $this->storeBefore($updateData, $id, $data);
        }
        $this->model::query()->where($this->id, $id)->update($updateData);
        $info = $this->model::find($id);
        if (method_exists($this, "storeAfter")) {
            $this->storeAfter($info, $updateData, $data);
        }
        App::db()->getConnection()->commit();
        return send($response, "更改成功");
    }

    /**
     * 删除数据
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function del(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $id = $args["id"] ?: 0;
        $name = $this->name ?? "";
        $query = $this->model::query();
        if (method_exists($this, "delWhere")) {
            $info = $this->delWhere($query, $args)->first();
        } else {
            $info = $query->where($this->id, $id)->first();
        }
        App::db()->getConnection()->beginTransaction();
        if (method_exists($this, "delBefore")) {
            $this->delBefore($info, $args);
        }
        $info->delete();
        if (method_exists($this, "delAfter")) {
            $this->delAfter($info, $args);
        }
        App::db()->getConnection()->commit();
        return send($response, "删除{$name}成功");
    }

    /**
     * 清除数据
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function trashed(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $id = $args["id"] ?: 0;
        $name = $this->name ?? "";
        $query = $this->model::query();
        if (method_exists($this, "trashedWhere")) {
            $info = $this->trashedWhere($query, $args)->withTrashed()->first();
        } else {
            $info = $query->where($this->id, $id)->withTrashed()->first();
        }
        App::db()->getConnection()->beginTransaction();
        if (method_exists($this, "trashedBefore")) {
            $this->trashedBefore($info, $args);
        }
        $info->forceDelete();
        if (method_exists($this, "trashedAfter")) {
            $this->trashedAfter($info, $args);
        }
        App::db()->getConnection()->commit();
        return send($response, "清除{$name}成功");
    }

    /**
     * 恢复数据
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function restore(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $id = $args["id"] ?: 0;
        $name = $this->name ?? "";
        $query = $this->model::query();
        if (method_exists($this, "restoreWhere")) {
            $info = $this->restoreWhere($query, $args)->withTrashed()->first();
        } else {
            $info = $query->withTrashed()->where($this->id, $id)->first();
        }
        App::db()->getConnection()->beginTransaction();
        if (method_exists($this, "restoreBefore")) {
            $this->restoreBefore($info, $args);
        }
        $info->restore();
        if (method_exists($this, "restoreAfter")) {
            $this->restoreAfter($info, $args);
        }
        App::db()->getConnection()->commit();
        return send($response, "清除{$name}成功");
    }
}