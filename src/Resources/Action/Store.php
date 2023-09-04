<?php

namespace Dux\Resources\Action;

use Dux\App;
use Dux\Handlers\ExceptionBusiness;
use Dux\Validator\Data;
use Dux\Validator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

trait Store
{
    public function store(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->init($request, $response, $args);
        $id = $args["id"];

        $jsonData = $request->getParsedBody();
        $keys = array_keys($jsonData);

        $requestData = [...$request->getParsedBody(), ...$args];
        $validator = array_filter($this->validator($requestData, $request, $args), function ($item, $key) use($keys) {
            if (in_array($key, $keys)) {
                return true;
            }
            return false;
        });

        $data = Validator::parser($requestData, $validator);
        $ruleData = array_filter($this->format($data, $request, $args), function ($item, $key) use ($keys) {
            if (in_array($key, $keys)) {
                return true;
            }
            return false;
        });

        $modelData = $this->formatData($ruleData, $data);

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

        $this->storeBefore($data, $model);

        $model->save();


        $this->storeAfter($data, $model);

        App::db()->getConnection()->commit();

        return send($response, "更新{$this->name}成功");
    }

    public function storeBefore(Data $data, mixed $info): void {
    }

    public function storeAfter(Data $data, mixed $info): void {
    }

}