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
}
