<?php
declare(strict_types=1);

namespace Dux\Permission\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Permission {

    public function __construct(
        string $name,
        string  $label = "") {}
}