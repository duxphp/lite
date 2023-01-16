<?php

namespace Dux\Validator;

class Data {

    protected array $array = [];

    function __set($key, $value) {
        $this->array[$key] = $value;
    }

    function __get($key) {
        return $this->array[$key];
    }
}