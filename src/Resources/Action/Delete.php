<?php

namespace Dux\Resources\Action;

use Dux\App;
use Dux\Handlers\ExceptionBusiness;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

trait Delete
{
    public function del(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->init($request, $response, $args);
        $id = $args["id"];

        App::db()->getConnection()->beginTransaction();

        $query = $this->model::query()->where($this->key, $id);
        $this->queryOne($query, $request, $args);
        $this->query($query);

        $model = $query->first();
        if (!$model) {
            throw new ExceptionBusiness('Data empty');
        }

        if (isset($this->delHook[0]) && $this->delHook[0] instanceof \Closure) {
            $this->delHook[0]($model, );
        }

        $this->delBefore($model);

        $model->delete();

        $this->delAfter($model);

        App::db()->getConnection()->commit();

        return send($response, "删除{$this->name}成功");
    }

    public function delBefore(mixed $info): void {
    }
    public function delAfter(mixed $info): void {
    }

}