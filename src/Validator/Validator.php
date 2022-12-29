<?php

namespace Dux\Validator;

// https://github.com/vlucas/valitron
use Dux\Handlers\ExceptionBusiness;

class Validator {

    /**
     * parser
     * @param array $data data array
     * @param array $rules ["name" => ["rule", "message"]]
     * @return object
     */
    static function parser(array $data, array $rules): object {
//        $role = [
//            "name" => ["rule", "message"]
//        ];
        $v = new \Valitron\Validator($data);
        foreach ($rules as $key => $item) {
            $v->rule($item[0], $key, $item[2] ?? false)->message($item[1]);
        }
        if(!$v->validate()) {
            $col = collect($v->errors());
            throw new ExceptionBusiness($col->first()[0], 500);
        }
        return (object)$data;
    }

}