<?php

namespace Honeybadger\Tests;

use Honeybadger\Support\Arr;
use PHPUnit\Framework\TestCase;

class ArrTest extends TestCase
{
    /** @test */
    public function it_will_map_with_keys()
    {
        $array = [
            'foo' => 'bar',
            'baz' => 'bax',
        ];

        $result = Arr::mapWithKeys($array, function ($item, $key) {
            return $item.'qux';
        });

        $this->assertEquals([
            'foo' => 'barqux',
            'baz' => 'baxqux',
        ], $result);
    }

    /** @test */
    public function can_get_an_item_from_an_array()
    {
        $this->assertEquals('bar', Arr::get(['foo' => 'bar'], 'foo'));
    }

    /** @test */
    public function will_use_default_if_key_does_not_exist()
    {
        $this->assertEquals('bar', Arr::get(['foo' => 'baz'], 'baz', 'bar'));
    }

    /** @test */
    public function array_is_associative()
    {
        $this->assertTrue(Arr::isAssociative(['foo' => 'bar']));
        $this->assertFalse(Arr::isAssociative(['foo']));
    }
}
