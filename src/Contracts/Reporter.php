<?php

namespace Honeybadger\Contracts;

use Throwable;
use Symfony\Component\HttpFoundation\Request as FoundationRequest;

interface Reporter
{
    /**
     * @param  \Throwable  $throwable
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  array  $options
     * @return array
     *
     * @throws \Honeybadger\Exceptions\ServiceException
     */
    public function notify(Throwable $throwable, FoundationRequest $request = null, array $options = []) : array;

    /**
     * @param  array  $payload
     * @return array
     *
     * @throws \Honeybadger\Exceptions\ServiceException
     */
    public function customNotification(array $payload) : array;

    /**
     * @param  callable  $callable
     * @return array
     *
     * @throws \Honeybadger\Exceptions\ServiceException
     * @throws \InvalidArgumentException
     */
    public function rawNotification(callable $callable) : array;

    /**
     * @param  string  $key
     * @return void
     *
     * @throws \Honeybadger\Exceptions\ServiceException
     */
    public function checkin(string $key) : void;

    /**
     * @param  int|string  $key
     * @param  int|string  $value
     * @return void
     */
    public function context($key, $value) : void;
}
