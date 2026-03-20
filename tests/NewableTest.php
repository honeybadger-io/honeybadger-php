<?php

namespace Honeybadger\Tests;

use PHPUnit\Framework\TestCase;
use Honeybadger\Tests\Fixtures\NewableFixture;

class NewableTest extends TestCase
{
    /** @test */
    public function it_will_create_an_instance()
    {
        $testClass = NewableFixture::new();

        $this->assertInstanceOf(NewableFixture::class, $testClass);
    }

    /** @test */
    public function it_will_pass_artuments_to_the_constructor()
    {
        $testClass = NewableFixture::new('foo', 'bar');

        $this->assertEquals('foo', $testClass->foo);
        $this->assertEquals('bar', $testClass->bar);
    }
}
