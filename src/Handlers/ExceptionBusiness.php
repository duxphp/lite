<?php

namespace Dux\Handlers;


use Slim\Exception\HttpException;

/**
 * 业务异常
 * 用于返回用户业务异常，不记录日志
 */
class ExceptionBusiness  extends Exception {

}