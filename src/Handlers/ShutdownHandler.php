<?php

namespace Honeybadger\Handlers;

use Honeybadger\Contracts\Handler as HandlerContract;

class ShutdownHandler extends Handler implements HandlerContract
{
    /**
     * @return void
     */
    public function register(): void
    {
        register_shutdown_function([$this, 'handle']);
    }

    /**
     * @return void
     *
     * @throws \Honeybadger\Exceptions\ServiceException
     */
    public function handle(): void
    {
        $this->honeybadger->flushEvents();
    }
}
