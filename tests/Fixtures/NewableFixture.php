<?php

namespace Honeybadger\Tests\Fixtures;

use Honeybadger\Concerns\Newable;

class NewableFixture
{
    use Newable;

    public $foo;
    public $bar;

    public function __construct($foo = null, $bar = null)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}
