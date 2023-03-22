<?php

namespace Dux\Server\Context;

final class Context
{
    public array $data = [];


    public function hasData(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function getData(string $name): mixed
    {
        return $this->data[$name];
    }

    public function setData(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

}
