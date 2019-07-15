<?php

namespace Honeybadger\Tests;

use Exception;
use Throwable;
use RuntimeException;
use Honeybadger\Honeybadger;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Honeybadger\BacktraceFactory;

class BacktraceFactoryTest extends TestCase
{
    /** @test */
    public function it_includes_the_correct_context_for_the_first_item()
    {
        try {
            $this->throwNestedExceptions();
        } catch (Throwable $e) {
            $backtrace = (new BacktraceFactory($e))->trace()[0];
        }

        $this->assertEquals('throwNestedExceptions', $backtrace['method']);
        $this->assertEquals(__FILE__, $backtrace['file']);

        $this->assertEquals(9, count($backtrace['source']));
    }

    /** @test */
    public function it_correctly_formats_annonymous_functions()
    {
        $throwTestException = function ($foo) {
            throw new Exception('test');
        };

        try {
            $throwTestException('bar');
        } catch (Throwable $e) {
            $backtrace = (new BacktraceFactory($e))->trace();
        }

        $this->assertEquals('Honeybadger\Tests\{closure}', $backtrace[0]['method']);
        $this->assertEquals(['bar'], $backtrace[0]['args']);
    }

    /** @test */
    public function it_correctly_formats_functions()
    {
        function throwTestException()
        {
            throw new Exception('Test');
        }

        try {
            throwTestException();
        } catch (Throwable $e) {
            $backtrace = (new BacktraceFactory($e))->trace();
        }

        $this->assertEquals('Honeybadger\Tests\throwTestException', $backtrace[0]['method']);
    }

    private function throwNestedExceptions()
    {
        try {
            throw new InvalidArgumentException('Exception One');
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException('Exception Two', null, $e);
        }
    }
}
