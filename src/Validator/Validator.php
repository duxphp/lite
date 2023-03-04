<?php
declare(strict_types=1);

namespace Dux\Validator;

// https://github.com/vlucas/valitron
use Dux\Handlers\ExceptionValidator;

class Validator
{

    /**
     * 数据验证
     * @param array $data data array
     * @param array $rules ["name" => ["rule", "message"]]
     */
    public static function parser(array $data, array $rules): Data
    {
//        $role = [
//            "name" => ["rule", "message"]
//        ];
        $v = new \Valitron\Validator($data);
        foreach ($rules as $key => $item) {
            if (empty($item)) {
                continue;
            }
            if (!is_array($item[0])) {
                $datas = [$item];
            } else {
                $datas = $item;
            }
            $keys = explode('#', $key);
            $key = trim($keys[0]);
            foreach ($datas as $vo) {
                $message = last($vo);
                $params = array_slice($vo, 1, -1);
                $v->rule($vo[0], $key, ...$params)->message($message);
            }
        }
        if (!$v->validate()) {
            throw new ExceptionValidator($v->errors());
        }
        $dataObj = new Data();
        foreach ($data as $k => $v) {
            $dataObj->$k = $v;
        }
        return $dataObj;
    }

}