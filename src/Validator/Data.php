<?php
declare(strict_types=1);

namespace Dux\Validator;

class Data {

    protected array $array = [];

    public function __set($key, $value) {
        $this->array[$key] = $value;
    }


    public function __isset($key) {
        return isset($this->array[$key]);
    }

    public function __get($key) {
        return $this->array[$key];
    }
}