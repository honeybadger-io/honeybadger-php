<?php

namespace Honeybadger;

use Honeybadger\TestCase;
use Honeybadger\Exception;

class ExceptionTests extends TestCase
{
    public function test_exception_handler_can_be_disabled()
    {
        $this->assertEquals([Exception::class, 'handle'], $this->getExceptionHandler());

        Exception::restore_handler();

        $this->assertNull($this->getExceptionHandler());
    }

    private function getExceptionHandler()
    {
        return set_exception_handler(function () {
            //
        });
    }
}
