<?php

namespace Dux\View;

class Translator
{
    public function __construct()
    {
    }

    public function translate(string $original, ...$params): string
    {
        return __($original, ...($params ?: ['theme']));
    }
}