<?php

namespace Honeybadger\Tests;

use Honeybadger\Tests\Fixtures\FiltersDataFixture;
use PHPUnit\Framework\TestCase;

class FiltersDataTest extends TestCase
{
    /** @test */
    public function it_will_filter_data()
    {
        $this->assertEquals(
            '[FILTERED]',
            (new FiltersDataFixture(['foo' => 'bar']))
                ->filterKeys(['foo'])
                ->data()['foo']
        );
    }

    /** @test */
    public function it_will_not_filter_index_arrays()
    {
        $this->assertEquals(
            ['bar' => ['baz']],
            (new FiltersDataFixture(['bar' => ['baz']]))
                ->filterKeys(['foo'])
                ->data()
        );
    }

    /** @test */
    public function it_will_filter_data_recursivly()
    {
        $filteredData = (new FiltersDataFixture(['foo' => ['bar' => 'baz']]))
            ->filterKeys(['bar'])
            ->data();

        $this->assertEquals(['foo' => ['bar' => '[FILTERED]']], $filteredData);
    }
}
