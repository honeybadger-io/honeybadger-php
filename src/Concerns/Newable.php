<?php

namespace Honeybadger\Concerns;

use ReflectionClass;

trait Newable
{
    /**
     * @return self
     */
    public static function new() : self
    {
        return (new ReflectionClass(static::class))
            ->newInstanceArgs(func_get_args());
    }
}
