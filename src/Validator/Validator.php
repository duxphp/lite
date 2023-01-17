<?php

namespace Dux\Validator;

// https://github.com/vlucas/valitron
use Dux\Handlers\ExceptionBusiness;

class Validator {

    /**
     * parser
     * @param array $data data array
     * @param array $rules ["name" => ["rule", "message"]]
     */
    static function parser(array $data, array $rules): Data {
//        $role = [
//            "name" => ["rule", "message"]
//        ];
        $v = new \Valitron\Validator($data);
        foreach ($rules as $key => $item) {
            if (empty($item)) {
                continue;
            }
            $message = last($item);
            $params = array_slice($item, 1, -1);
            $v->rule($item[0], $key, ...$params)->message($message);
        }
        if(!$v->validate()) {
            $col = collect($v->errors());
            throw new ExceptionBusiness($col->first()[0], 500);
        }
        $dataObj = new Data();
        foreach ($data as $k => $v) {
            $dataObj->$k = $v;
        }
        return $dataObj;
    }

}