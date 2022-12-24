<?php
declare(strict_types=1);

use Illuminate\Support\Carbon;

function now(): Carbon {
    return Carbon::now();
}