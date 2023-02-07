<?php

namespace Dux\Validator;

// https://github.com/vlucas/valitron
use Dux\Handlers\ExceptionValidator;

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
            $keys = explode('#', $key);
            $key = trim($keys[0]);
            $message = last($item);
            $params = array_slice($item, 1, -1);
            $v->rule($item[0], $key, ...$params)->message($message);
        }
        if(!$v->validate()) {
            throw new ExceptionValidator($v->errors());
        }
        $dataObj = new Data();
        foreach ($data as $k => $v) {
            $dataObj->$k = $v;
        }
        return $dataObj;
    }

}