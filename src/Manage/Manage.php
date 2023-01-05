<?php

namespace Dux\Manage;

use Dux\App;
use Dux\Handlers\ExceptionBusiness;
use Dux\Validator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @method listFormat(object $item): array
 * @method listAssign($query, $args): array
 * @method infoAssign(object $info): array
 * @method infoFormat(object $info): array
 * @method saveValidator(array $args): array
 * @method saveFormat(object $data, int $id): array
 * @method saveAfter($info)
 * @method delBefore($info)
 * @method delAfter($info)
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
        $limit = $args["limit"] ?: $this->listLimit;
        $treeStatus = $this->tree;
        $pageStatus = $this->listPage;
        $query = $this->model::query();
        if ($this->listFields) {
            $query = $query->select($this->listFields);
        }
        if ($treeStatus) {
            $query = $query->where("parent_id", 0)->with(['children']);
        }
        if (method_exists($this, "listWhere")) {
            $query = $this->listWhere($query, $args);
        }
        if ($pageStatus) {
            $query = $query->paginate($limit);
        } else {
            $query = $query->get();
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
            $query = $this->model::query();
            if (method_exists($this, "infoWhere")) {
                $info = $this->infoWhere($query, $args)->first();
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
            $data = [...$data, $this->infoAssign($info)];
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
        $treeStatus = $this->tree;
        $data = Validator::parser([...$request->getParsedBody(), ...$args], method_exists($this, "saveValidator") ? $this->saveValidator($args) : []);
        App::db()->getConnection()->beginTransaction();

        if ($id && $treeStatus) {
            $parentId = last($data->parent);
            if ($data->id == $parentId) {
                throw new ExceptionBusiness("当前分类不能成为上级分类");
            }
            $tree = $this->model::tree();
            $node = $tree->getNodeById($id);
            $descendants = array_column($node->getDescendants(), "id");
            if ($parentId && in_array($parentId, $descendants)) {
                throw new ExceptionBusiness("上级节点不能为子节点");
            }
        }

        if (method_exists($this, "saveFormat")) {
            $data = $this->saveFormat($data, $id);
        } else {
            $data = (array)$data;
        }
        if ($id) {
            // 编辑
            $info = $this->model::query()->where($this->id, $id)->update($data);
            if (method_exists($this, "saveAfter")) {
                $this->saveAfter($info);
            }
            App::db()->getConnection()->commit();
            return send($response, "编辑{$name}成功");
        } else {
            // 添加
            $info = $this->model::query()->create($data);
            if (method_exists($this, "saveAfter")) {
                $this->saveAfter($info);
            }
            App::db()->getConnection()->commit();
            return send($response, "添加{$name}成功");
        }
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
            $this->delBefore($info);
        }
        $this->model::query()->where($this->id, $info[$this->id])->delete();
        if (method_exists($this, "delAfter")) {
            $this->delAfter($info);
        }
        App::db()->getConnection()->commit();
        return send($response, "删除{$name}成功");
    }

    private ?\BlueM\Tree $treeData = null;

    /**
     * 转换级别数据
     * @param $id
     * @return array
     */
    protected function coverLevel($id): array {
        if (!$this->treeData) {
            $tree = $this->model::tree();
            $this->treeData = $tree;
        } else {
            $tree = $this->treeData;
        }
        $node = $tree->getNodeById($id);
        $ancestorsPlusSelf = $node->getAncestorsAndSelf();
        return array_reverse(array_column($ancestorsPlusSelf, "id"));
    }
}