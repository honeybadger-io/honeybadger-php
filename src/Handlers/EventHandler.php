<?php

namespace Honeybadger\Handlers;

abstract class EventHandler
{
    private $listeners = [];

    public function register(callable $closure): void
    {
        $this->listeners[] = $closure;
    }

    public function handle(array &$payload): bool
    {
        foreach ($this->listeners as $listener) {
            if ($listener($payload) === false) {
                return false;
            }
        }

        return true;
    }
}
