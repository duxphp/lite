<?php

namespace Dux\Validator;

// https://doc.nette.org/en/utils/validators
use InvalidArgumentException;
use Nette\Utils\Validators;

class Validator {

    /**
     * parser
     * @param array $data data array
     * @param array $rules ["name", "rule", "message"]
     * @return array
     */
    static function parser(array $data, array $rules): array {
//        $role = [
//            ["name", "rule", "message"],
//        ];

        $result = [];
        foreach ($rules as $item) {
            $status = true;
            if (is_callable($item[1])) {
                if (!call_user_func($item[1], $data[$item[0]])) {
                    $status = false;
                }
            } else {
                $status = Validators::is($data[$item[0]], $item[1]);
            }
            if (!$status) {
                throw new InvalidArgumentException($item[3] ?: "parameter {$item[0]} passed incorrectly");
            }
            $result[$item[0]] = $data[$item[0]];
        }
        return $result;
    }

}