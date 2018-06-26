<?php

namespace Honeybadger\Tests\Fixtures;

use Honeybadger\Concerns\Newable;
use Honeybadger\Concerns\FiltersData;

class FiltersDataFixture
{
    use Newable, FiltersData;

    public $items = [];

    public function __construct($items = [])
    {
        $this->items = $items;
    }

    public function data()
    {
        return $this->filter($this->items);
    }
}
