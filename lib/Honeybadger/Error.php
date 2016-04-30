<?php

namespace Honeybadger;

/**
 * Based on [Kohana's error handler](https://github.com/kohana/core/blob/3.3/master/classes/Kohana/Core.php#L984:L995).
 *
 * @package  Honeybadger
 */
class Error
{
    private static $previous_handler;

    public static function register_handler()
    {
        self::$previous_handler = set_error_handler(
            array(
                __CLASS__, 'handle',
            )
        );
    }

    public static function handle($code, $error, $file = null, $line = null)
    {
        if (error_reporting() & $code) {
            // This error is not suppressed by current error reporting settings.
            // Convert the error into an ErrorException.
            $exception = new \ErrorException($error, $code, 0, $file, $line);

            // Send the error to Honeybadger.
            Honeybadger::notify_or_ignore($exception);
        }

        if (is_callable(self::$previous_handler)) {
            // Pass the triggered error on to the previous error handler.
            return call_user_func(
                self::$previous_handler,
                $code,
                $error,
                $file,
                $line
            );
        }

        // Execute the PHP error handler.
        return false;
    }

} // End Error
