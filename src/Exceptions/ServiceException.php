<?php

namespace Honeybadger\Exceptions;

use Exception;

class ServiceException extends Exception
{
    /**
     * @return \Honeybadger\Exceptions\ServiceException
     */
    public static function invalidApiKey(): self
    {
        return new static('The API key provided is invalid.');
    }

    /**
     * @return \Honeybadger\Exceptions\ServiceException
     */
    public static function invalidPayload(): self
    {
        return new static('The payload sent to Honeybadger was invalid.');
    }

    /**
     * @return \Honeybadger\Exceptions\ServiceException
     */
    public static function rateLimit(): self
    {
        return new static('You have hit your exception rate limit.');
    }

    /**
     * @return \Honeybadger\Exceptions\ServiceException
     */
    public static function serverError(): self
    {
        return new static('There was an error on our end.');
    }

    /**
     * @return \Honeybadger\Exceptions\ServiceException
     */
    public static function generic(): self
    {
        return new static('There was an error sending the payload to Honeybadger.');
    }
}
