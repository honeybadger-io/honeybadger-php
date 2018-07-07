<?php

namespace Honeybadger\Tests;

use Exception;
use Honeybadger\Honeybadger;
use PHPUnit\Framework\TestCase;
use Honeybadger\Handlers\ErrorHandler;
use Honeybadger\Handlers\ExceptionHandler;
use Honeybadger\Tests\Fixtures\HandlerFixture;

class HandlerTest extends TestCase
{
    /** @test */
    public function exception_handler_get_set()
    {
        $handlerFixture = new HandlerFixture;
        set_exception_handler([$handlerFixture, 'exceptionHandler']);
        $mock = $this->createMock(Honeybadger::class);
        $mock->expects($this->once())->method('notify');

        $handler = new ExceptionHandler($mock);
        $handler->register();

        $handler->handle(new Exception('test 1234'));

        $currentHandler = set_exception_handler(null);

        $this->assertTrue($handlerFixture->called);
        $this->assertEquals('test 1234', $handlerFixture->args[0]->getMessage());

        $this->assertInstanceOf(ExceptionHandler::class, $currentHandler[0]);
    }

    /** @test */
    public function error_handler_get_set()
    {
        // cache previous error handler
        $previousHandler = set_error_handler(null);
        $handlerFixture = new HandlerFixture;
        set_error_handler([$handlerFixture, 'errorHandler']);

        $mock = $this->createMock(Honeybadger::class);
        $mock->expects($this->once())->method('notify');

        $handler = new ErrorHandler($mock);
        $handler->register();

        $handler->handle(0, 'asdf', $file = null, $line = null);

        $currentHandler = set_error_handler(null);
        $this->assertInstanceOf(ErrorHandler::class, $currentHandler[0]);

        $this->assertTrue($handlerFixture->called);
        $this->assertEquals([
            0,
            'asdf',
            null,
            null,
        ], $handlerFixture->args);

        // Restore phpunithandler
        set_error_handler($previousHandler);
    }
}
