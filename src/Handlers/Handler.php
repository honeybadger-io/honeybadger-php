<?php

namespace Honeybadger\Handlers;

use Honeybadger\Honeybadger;

abstract class Handler
{
    /**
     * @var \Honeybadger\Honeybadger
     */
    protected $honeybadger;

    /**
     * @param  \Honeybadger\Honeybadger  $honeybadger
     */
    public function __construct(Honeybadger $honeybadger)
    {
        $this->honeybadger = $honeybadger;
    }
}
