<?php

namespace Dux\Database\DbDrives;

use Illuminate\Database\Capsule\Manager;

interface DriveInterface
{
    public function init(array $configs): void;

    public function get(): Manager;

    public function release(): void;
}