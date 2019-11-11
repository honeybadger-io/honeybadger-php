<?php

namespace Honeybadger\Handlers;

use Honeybadger\Contracts\Handler as HandlerContract;
use Throwable;

class ExceptionHandler extends Handler implements HandlerContract
{
    /**
     * @var callable
     */
    protected $previousHandler;

    /**
     * @return void
     */
    public function register() : void
    {
        $this->previousHandler = set_exception_handler([$this, 'handle']);
    }

    /**
     * @param  \Throwable  $e
     * @return void
     *
     * @throws \Honeybadger\Exceptions\ServiceException
     */
    public function handle(Throwable $e) : void
    {
        $this->honeybadger->notify($e);

        call_user_func($this->previousHandler, $e);
    }
}
