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

    public static function unexpectedResponseCode(int $code): self
    {
        return new static("Unexpected HTTP response code: $code");
    }

    public static function generic(\Throwable $e = null): self
    {
        $message = $e
            ? 'There was an error sending the payload to Honeybadger: '.$e->getMessage()
            : 'There was an error sending the payload to Honeybadger.';
        return new static($message, 0, $e);
    }
}
