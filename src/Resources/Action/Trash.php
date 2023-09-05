<?php

namespace Dux\Resources\Action;

use Dux\App;
use Dux\Handlers\ExceptionBusiness;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

trait Trash
{
    public function trash(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->init($request, $response, $args);
        $id = $args["id"];

        App::db()->getConnection()->beginTransaction();

        $query = $this->model::query()->where($this->key, $id);
        $this->queryOne($query, $request, $args);
        $this->query($query);

        $model = $query->withTrashed()->first();
        if (!$model) {
            throw new ExceptionBusiness('Data empty');
        }

        $this->trashBefore($model);

        $model->forceDelete();

        $this->trashAfter($model);

        App::db()->getConnection()->commit();

        return send($response, "彻底删除{$this->name}成功");
    }

    public function trashBefore(mixed $info): void
    {
    }

    public function trashAfter(mixed $info): void
    {
    }

}