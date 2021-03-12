<?php

namespace Honeybadger\Tests;

use Honeybadger\Breadcrumbs;
use PHPUnit\Framework\TestCase;
use stdClass;

class BreadcrumbsTest extends TestCase
{
    /** @test */
    public function can_add_breadcrumbs()
    {
        $breadcrumbs = new Breadcrumbs(3);

        $item = [
            'message' => 'Database query',
            'metadata' => [
                'query' => 'SELECT things',
            ],
            'category' => 'query',
            'timestamp' => time(),
        ];
        $breadcrumbs->add($item);

        $items = $breadcrumbs->toArray();

        $this->assertEquals([$item], $items);
    }

    /** @test */
    public function limits_the_length_of_breadcrumbs()
    {
        $size = rand(1, 3);
        $breadcrumbs = new Breadcrumbs($size);
        $added = [];
        foreach (range(0, 5) as $i) {
            $item = ['message' => "$i", 'metadata' => []];
            $added[] = $item;
            $breadcrumbs->add($item);
        }

        $items = $breadcrumbs->toArray();
        $this->assertCount($size, $items);

        $expected = array_slice($added, 6 - $size);

        foreach ($items as $key => $item) {
            $this->assertEquals($expected[$key]['message'], $item['message']);
            $this->assertEquals($expected[$key]['metadata'], $item['metadata']);
        }
    }

    /** @test */
    public function removes_nested_and_nonprimitive_items_in_metadata()
    {
        $breadcrumbs = new Breadcrumbs(3);

        $item = [
            'message' => 'A thing',
            'metadata' => [
                'object' => new stdClass(),
                'array' => ['a' => 'b'],
                'string' => 'hello',
                'integer' => 3,
                'float' => 3.5,
            ],
            'timestamp' => 123538954,
        ];
        $breadcrumbs->add($item);

        $items = $breadcrumbs->toArray();

        $this->assertEquals([
            [
                'message' => 'A thing',
                'metadata' => [
                    'string' => 'hello',
                    'integer' => 3,
                    'float' => 3.5,
                    'object' => '[DEPTH]',
                    'array' => '[DEPTH]',
                ],
                'category' => 'custom',
                'timestamp' => 123538954,
            ],
        ], $items);
    }
}
