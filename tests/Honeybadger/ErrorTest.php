<?php

namespace Honeybadger;

use Honeybadger\Error;
use Honeybadger\TestCase;

class ErrorTest extends TestCase
{
    /**
     * This will fail if ran under a filter, needs to be run with the entire
     * test suite due to the way PHP and PHPUnit handle error handlers
     */
    public function test_error_handler_can_be_disabled()
    {
        Error::register_handler();
        $this->assertEquals([Error::class, 'handle'], $this->getErrorHandler());

        Error::restore_handler();
        $this->assertTrue(in_array($this->getErrorHandler()[0], [
            \PHPUnit\Util\ErrorHandler::class,
            'PHPUnit_Util_ErrorHandler'
        ]));
    }

    private function getErrorHandler()
    {
        return set_error_handler(function () {
            //
        });
    }
}
