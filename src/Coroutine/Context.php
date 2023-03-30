<?php

namespace Dux\Coroutine;

class Context
{
    public array $data;
    public array $destroys = [];

    public function getValue(string $key): mixed
    {
        return $this->data[$key];
    }

    public function setValue(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function hasValue(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function setDestroy(callable $callback): void
    {
        $this->destroys[] = $callback;
    }

    public function destroy(): void
    {
        foreach ($this->destroys as $item) {
            $item();
        }
    }
}