<?php
declare(strict_types=1);

namespace Dux\Database;

use Medoo\Medoo;

class MedooExtend extends Medoo
{
    protected array $config = [];
    public function __construct(array $options) {
        $this->config = $options;
        parent::__construct($options);
    }

    public function getConfig(string $key = ""): mixed {
        return $key ? $this->config[$key] : $this->config;
    }
}