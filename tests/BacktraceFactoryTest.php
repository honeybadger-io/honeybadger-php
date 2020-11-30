<?php

namespace Honeybadger\Tests;

use Exception;
use Honeybadger\BacktraceFactory;
use Honeybadger\Config;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class BacktraceFactoryTest extends TestCase
{
    /** @test */
    public function it_includes_the_correct_context_for_the_first_item()
    {
        try {
            $this->throwNestedExceptions();
        } catch (Throwable $e) {
            $backtrace = (new BacktraceFactory($e, new Config))->trace()[0];
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
            $backtrace = (new BacktraceFactory($e, new Config))->trace();
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
            $backtrace = (new BacktraceFactory($e, new Config))->trace();
        }

        $this->assertEquals('Honeybadger\Tests\throwTestException', $backtrace[0]['method']);
    }

    /** @test */
    public function bactraces_send_class()
    {
        try {
            throw new Exception('test');
        } catch (Throwable $e) {
            $backtrace = (new BacktraceFactory($e, new Config))->trace();
        }

        $this->assertEquals(self::class, $backtrace[0]['class']);
    }

    /** @test */
    public function bactraces_send_type()
    {
        try {
            throw new Exception('test');
        } catch (Throwable $e) {
            $backtrace = (new BacktraceFactory($e, new Config))->trace();
        }

        $this->assertEquals('->', $backtrace[0]['type']);
    }

    /** @test */
    public function bactraces_send_type_for_static()
    {
        try {
            self::throwStaticException();
        } catch (Throwable $e) {
            $backtrace = (new BacktraceFactory($e, new Config))->trace();
        }

        $this->assertEquals('::', $backtrace[0]['type']);
    }

    /** @test */
    public function context_is_sent_for_frames()
    {
        try {
            throw new Exception('test');
        } catch (Throwable $e) {
            $backtrace = (new BacktraceFactory($e, new Config))->trace();
        }

        $this->assertEquals('app', $backtrace[0]['context']);
    }

    /** @test */
    public function context_is_sent_as_vendor()
    {
        $config = new Config([
            'project_root' => dirname(getcwd().'/..'),
            'vendor_paths' => ['tests\/.*'],
        ]);

        try {
            throw new Exception('test');
        } catch (Throwable $e) {
            $backtrace = (new BacktraceFactory($e, $config))->trace();
        }

        $this->assertEquals('all', $backtrace[0]['context']);
    }

    protected static function throwStaticException()
    {
        throw new Exception('test');
    }

    private function throwNestedExceptions()
    {
        try {
            throw new InvalidArgumentException('Exception One');
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException('Exception Two', null, $e);
        }
    }

    /** @test */
    public function args_with_object_should_be_class_names()
    {
        $throwTestException = function ($foo, $bar) {
            throw new Exception('test');
        };

        try {
            $throwTestException('bar', new TestClass);
        } catch (Throwable $e) {
            $backtrace = (new BacktraceFactory($e, new Config))->trace();
        }

        $this->assertEquals('Honeybadger\Tests\{closure}', $backtrace[0]['method']);
        $this->assertEquals(['bar', TestClass::class], $backtrace[0]['args']);
    }
}

class TestClass
{
    protected $foo = 'bar';

    public function __construct()
    {
        //
    }
}
