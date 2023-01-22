<?php
declare(strict_types=1);

namespace Dux\Handlers;

use Throwable;

/**
 * ExceptionValidator
 */
class ExceptionValidator  extends ExceptionData {

    public function __construct(string $message, array $data) {
        parent::__construct($message, 422);
        $this->data = $data;
    }

    public function getDescription() {
        $errors = array_values($this->data);
        return $errors[0] ? $errors[0][0] : '';
    }
}