<?php

namespace Dux\Database\Drives;

use Illuminate\Database\Capsule\Manager;

interface DriveInterface
{
    public function init(array $configs): void;

    public function get(): Manager;
}