<?php

namespace Honeybadger\Tests;

use Exception;
use Honeybadger\Handlers\ErrorHandler;
use Honeybadger\Handlers\ExceptionHandler;
use Honeybadger\Honeybadger;
use Honeybadger\Tests\Fixtures\HandlerFixture;
use PHPUnit\Framework\TestCase;
use Throwable;

class HandlerTest extends TestCase
{
    /** @test */
    public function exception_handler_gets_set()
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
    public function error_handler_gets_set()
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

        // Restore PHPUnit's handler
        set_error_handler($previousHandler);
    }

    /** @test */
    public function ignores_silenced_errors_properly()
    {
        // Cache previous error handler
        $previousHandler = set_error_handler(null);

        $mock = $this->createMock(Honeybadger::class);
        $mock->expects($this->never())->method('notify');

        $handler = new ErrorHandler($mock);
        $handler->register();

        $x = [];
        @$x[3]++;

        // Restore PHPUnit's handler
        set_error_handler($previousHandler);
    }

    /** @test */
    public function does_not_ignore_php8_unsilenceable_errors()
    {
        if (PHP_MAJOR_VERSION < 8) {
            $this->markTestSkipped('All errors can be silenced on PHP < 8');
        }

        // Cache previous error handler
        $previousHandler = set_error_handler(null);

        $mock = $this->createMock(Honeybadger::class);
        $mock->expects($this->once())->method('notify')
            ->willReturnCallback(function (Throwable $exception) {
                $this->assertInstanceOf(\ErrorException::class, $exception);
                $this->assertEquals('A fatal error which can not be silenced', $exception->getMessage());

                return [];
            });

        $handler = new ErrorHandler($mock);
        $handler->register();

        @trigger_error('A fatal error which can not be silenced', E_USER_ERROR);

        // Restore PHPUnit's handler
        set_error_handler($previousHandler);
    }
}
