<?php

namespace Honeybadger\Tests\Fixtures;

class HandlerFixture
{
    public $called = false;
    public $args = [];

    public function exceptionHandler($e)
    {
        $this->called = true;
        $this->args = func_get_args();

        return $this;
    }

    public function errorHandler()
    {
        $this->called = true;
        $this->args = func_get_args();
    }
}
