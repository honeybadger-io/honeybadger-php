<?php

namespace Honeybadger\Tests;

use Closure;
use Honeybadger\ArgumentValueNormalizer;
use Honeybadger\Honeybadger;
use PHPUnit\Framework\TestCase;
use stdClass;

class ArgumentValueNormalizerTest extends TestCase
{
    /** @test */
    public function it_normalizes_simple_primitives_as_they_are()
    {
        $integer = rand();
        $this->assertEquals($integer, ArgumentValueNormalizer::normalize($integer));

        $float = rand() / rand();
        $this->assertEquals($float, ArgumentValueNormalizer::normalize($float));

        $string = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 13);
        $this->assertEquals($string, ArgumentValueNormalizer::normalize($string));

        $this->assertEquals(false, ArgumentValueNormalizer::normalize(false));
        $this->assertEquals(true, ArgumentValueNormalizer::normalize(true));

        $array = [rand(), $string];
        $this->assertEquals($array, ArgumentValueNormalizer::normalize($array));
    }

    /** @test */
    public function it_normalizes_objects_to_literals()
    {
        $obj = new stdClass();
        $honeybadger = new Honeybadger([]);
        $this->assertEquals('[LITERAL]Object('.stdClass::class.')', ArgumentValueNormalizer::normalize($obj));
        $this->assertEquals('[LITERAL]Object('.Honeybadger::class.')', ArgumentValueNormalizer::normalize($honeybadger));
    }

    /** @test */
    public function it_normalizes_closures_to_literals()
    {
        $closure = function ($something) {
        };
        $this->assertEquals('[LITERAL]Object('.Closure::class.')', ArgumentValueNormalizer::normalize($closure));
    }

    /** @test */
    public function it_limits_number_of_keys_in_array()
    {
        $array = ['a' => 18567, 'b' => '97ndfs', 'c' => 97874, 'd' => 'hehehe'];

        $normalizer = new class extends ArgumentValueNormalizer {
            protected const MAX_KEYS_IN_ARRAY = 2;
        };
        $keys = array_keys($normalizer::normalize($array));
        $this->assertCount(2, $keys);
        $this->assertContains('a', $keys);
        $this->assertContains('b', $keys);
        $this->assertNotContains('c', $keys);
        $this->assertNotContains('d', $keys);

        $normalizer = new class extends ArgumentValueNormalizer {
            protected const MAX_KEYS_IN_ARRAY = 1;
        };
        $keys = array_keys($normalizer::normalize($array));
        $this->assertCount(1, $keys);
        $this->assertContains('a', $keys);
        $this->assertNotContains('b', $keys);
        $this->assertNotContains('c', $keys);
        $this->assertNotContains('d', $keys);
    }

    /** @test */
    public function it_limits_array_depth()
    {
        $array = [
            'a' => [
                'b' => [
                    'c' => new stdClass(),
                    'd' => 1,
                    'e' => [
                        'f' => 3,
                    ],
                ],
            ],
        ];

        $normalizer = new class extends ArgumentValueNormalizer {
            protected const MAX_DEPTH = 2;
        };
        $normalized = $normalizer::normalize($array);
        $expected = [
            'a' => [
                'b' => [
                    'c' => '[LITERAL]Object(stdClass)',
                    'd' => 1,
                    'e' => 'Array(1 item)',
                ],
            ],
        ];
        $this->assertEquals($expected, $normalized);

        $normalizer = new class extends ArgumentValueNormalizer {
            protected const MAX_DEPTH = 1;
        };
        $normalized = $normalizer::normalize($array);
        $expected = [
            'a' => [
                'b' => 'Array(3 items)',
            ],
        ];
        $this->assertEquals($expected, $normalized);
    }
}
