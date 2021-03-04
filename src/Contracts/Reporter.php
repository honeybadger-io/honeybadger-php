<?php

namespace Honeybadger\Contracts;

use Symfony\Component\HttpFoundation\Request as FoundationRequest;
use Throwable;

interface Reporter
{
    /**
     * @param  \Throwable  $throwable
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  array  $additionalParams
     * @return array
     *
     * @throws \Honeybadger\Exceptions\ServiceException
     */
    public function notify(Throwable $throwable, FoundationRequest $request = null, array $additionalParams = []): array;

    /**
     * @param  array  $payload
     * @return array
     *
     * @throws \Honeybadger\Exceptions\ServiceException
     */
    public function customNotification(array $payload): array;

    /**
     * @param  callable  $callable
     * @return array
     *
     * @throws \Honeybadger\Exceptions\ServiceException
     * @throws \InvalidArgumentException
     */
    public function rawNotification(callable $callable): array;

    /**
     * @param  string  $key
     * @return void
     *
     * @throws \Honeybadger\Exceptions\ServiceException
     */
    public function checkin(string $key): void;

    /**
     * @param  int|string|array  $key
     * @param  int|string|array|null  $value
     * @return self
     */
    public function context($key, $value = null): self;
}
