<?php

namespace Honeybadger\Exceptions;

use Exception;
use Throwable;

class ServiceException extends Exception
{
    /**
     * @return ServiceException
     */
    public static function invalidApiKey(): self
    {
        return new static('The API key provided is invalid.');
    }

    /**
     * @return ServiceException
     */
    public static function invalidPayload(): self
    {
        return new static('The payload sent to Honeybadger was invalid.');
    }

    /**
     * @return ServiceException
     */
    public static function rateLimit(): self
    {
        return new static('You have hit your exception rate limit.');
    }

    /**
     * @return ServiceException
     */
    public static function eventsRateLimit(): self
    {
        return new static('You have hit your events rate limit.');
    }

    /**
     * @return ServiceException
     */
    public static function serverError(): self
    {
        return new static('There was an error on our end.');
    }

    /**
     * @param int $code
     * @return ServiceException
     */
    public static function unexpectedResponseCode(int $code): self
    {
        return new static("Unexpected HTTP response code: $code");
    }

    /**
     * @param Throwable|null $e
     * @return self
     */
    public static function generic(Throwable $e = null): self
    {
        $message = $e
            ? 'There was an error sending the payload to Honeybadger: '.$e->getMessage()
            : 'There was an error sending the payload to Honeybadger.';

        return new static($message, 0, $e);
    }

    /**
     * @param string $message
     * @return self
     */
    public static function invalidConfig(string $message): self
    {
        return new static("The configuration is invalid: $message");
    }

    /**
     * @return self
     */
    public static function missingPersonalAuthToken(): self
    {
        return new static("Missing personal auth token. This token is required to use Honeybadger's Data APIs.");
    }
}
