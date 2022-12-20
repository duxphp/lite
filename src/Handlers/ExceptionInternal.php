<?php

namespace Dux\Handlers;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpSpecializedException;
use Throwable;

/**
 * 内部异常
 * 用于返回系统内部异常，记录异常日志
 */
class ExceptionInternal  extends \Exception {

    public function __construct($message = "", $code = 500, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}