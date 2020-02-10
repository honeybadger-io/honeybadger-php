<?php

namespace Honeybadger\Contracts;

interface Handler
{
    /**
     * @return void
     */
    public function register(): void;
}
