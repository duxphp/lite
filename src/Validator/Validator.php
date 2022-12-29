<?php

namespace Dux\Validator;

// https://doc.nette.org/en/utils/validators
use InvalidArgumentException;
use Nette\Utils\Validators;

class Validator {

    /**
     * parser
     * @param array $data data array
     * @param array $rules ["name" => ["rule", "message"]]
     * @return array
     */
    static function parser(array $data, array $rules): array {
//        $role = [
//            "name" => ["rule", "message"]
//        ];

        $result = [];
        foreach ($rules as $key => $item) {
            if (!$item) {
                $result[$key] = $data[$key];
                continue;
            }
            $status = true;
            if (is_callable($item[0])) {
                if (!call_user_func($item[0], $data[$key])) {
                    $status = false;
                }
            } else {
                $status = Validators::is($data[$key], $item[0]);
            }
            if (!$status) {
                throw new InvalidArgumentException($item[1] ?: "parameter {$key} passed incorrectly");
            }
            $result[$key] = $data[$key];
        }
        return $result;
    }

}