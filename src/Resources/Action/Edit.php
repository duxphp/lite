<?php

namespace Dux\Resources\Action;

use Dux\App;
use Dux\Handlers\ExceptionBusiness;
use Dux\Validator\Data;
use Dux\Validator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

trait  Edit
{
    public function edit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->init($request, $response, $args);
        $id = (int) $args["id"];

        $requestData = [...$request->getParsedBody(), ...$args];
        $data = Validator::parser($requestData, $this->validator($requestData, $request, $args));

        $modelData = $this->formatData($this->format($data, $request, $args), $data);

        App::db()->getConnection()->beginTransaction();
        $query = $this->model::query()->where($this->key, $id);
        $this->queryOne($query, $request, $args);
        $this->query($query);

        $model = $query->first();
        if (!$model) {
            throw new ExceptionBusiness('Data empty');
        }

        foreach ($modelData as $key => $vo) {
            $model->$key = $vo;
        }

        $this->editBefore($data, $model);

        $model->save();

        $this->editAfter($data, $model);

        App::db()->getConnection()->commit();

        return send($response, "编辑{$this->name}成功");
    }

    public function editBefore(Data $data, mixed $info): void {
    }
    public function editAfter(Data $data, mixed $info): void {
    }
}