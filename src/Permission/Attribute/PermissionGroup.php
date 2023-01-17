<?php
declare(strict_types=1);

namespace Dux\Permission\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class PermissionGroup {

    public function __construct(
        string $app,
        string $name,
        string  $label = "",
        int $order = 0
    ) {}
}