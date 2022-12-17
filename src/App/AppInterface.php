<?php
declare(strict_types=1);

namespace Dux\App;

interface AppInterface {

    // 注册应用
    public function register();

    // 启动应用
    public function boot();

    // 最后执行
    public function after();
}