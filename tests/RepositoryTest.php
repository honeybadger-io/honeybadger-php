<?php

namespace Honeybadger\Tests;

use Honeybadger\Support\Repository;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    /** @test */
    public function it_implements_array_access()
    {
        $repository = new Repository(['foo' => 'bar']);

        $this->assertTrue(isset($repository['foo']));
        $this->assertEquals('bar', $repository['foo']);

        $repository['baz'] = 'bax';
        $this->assertEquals('bax', $repository['baz']);

        unset($repository['foo']);
        $this->assertFalse(isset($repository['foo']));
    }

    /** @test */
    public function it_will_get_items()
    {
        $repository = new Repository(['foo' => 'bar', 'baz' => 'bax', 'qaz' => 'qux']);

        $this->assertEquals(
            ['foo' => 'bar', 'baz' => 'bax', 'qaz' => 'qux'],
            $repository->all()
       );
    }

    /** @test */
    public function it_will_set_a_value_using_a_setter()
    {
        $repository = new Repository(['foo' => 'bar']);
        $repository->set('baz', 'bax');

        $this->assertEquals(
            ['foo' => 'bar', 'baz' => 'bax'],
            $repository->all()
        );
    }

    /** @test */
    public function it_will_add_items_via_attribute()
    {
        $repository = new Repository;
        $repository->foo = 'bar';

        $this->assertEquals(['foo' => 'bar'], $repository->all());
    }

    /** @test */
    public function it_will_exclude_multiple_keys()
    {
        $repository = new Repository(['foo' => 'bar', 'baz' => 'bar', 'qux' => 'bar']);
        $items = $repository->except(['foo', 'baz']);

        $this->assertArrayNotHasKey('foo', $items);
        $this->assertArrayNotHasKey('baz', $items);
    }

    /** @test */
    public function it_will_exclude_a_signel_key()
    {
        $repository = new Repository(['foo' => 'bar', 'baz' => 'bar', 'qux' => 'bar']);
        $items = $repository->except('foo');

        $this->assertArrayNotHasKey('foo', $items);
    }
}
