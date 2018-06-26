<?php

namespace Honeybadger\Handlers;

use ErrorException;
use Honeybadger\Contracts\Handler as HandlerContract;

class ErrorHandler extends Handler implements HandlerContract
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
        $this->previousHandler = set_error_handler([$this, 'handle']);
    }

    /**
     * @param  int  $code
     * @param  string  $error
     * @param  string  $file
     * @param  int  $line
     * @return void
     *
     * @throws \Honeyhadger\Exceptions\ServiceException
     */
    public function handle($code, $error, $file = null, $line = null) : void
    {
        $this->honeybadger->notify(
            new ErrorException($error, $code, 0, $file, $line)
        );

        call_user_func($this->previousHandler, $code, $error, $file, $line);
    }
}
