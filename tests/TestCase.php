<?php
namespace Dux\tests;

class TestCase extends \PHPUnit\Framework\TestCase{

    public function setUp(): void {
        \Dux\App::createCli(__DIR__ .'/../../..');
    }
}