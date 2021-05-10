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
     * The fatal error types that cannot be silenced using the @ operator in PHP 8+.
     */
    private const PHP8_UNSILENCEABLE_ERRORS = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
        E_USER_ERROR,
        E_RECOVERABLE_ERROR
    ];

    /**
     * @return void
     */
    public function register(): void
    {
        $this->previousHandler = set_error_handler([$this, 'handle']);
    }

    public function handle(int $level, string $error, string $file = null, int $line = null)
    {
        // When the @ operator is used, it temporarily changes `error_reporting()`'s return value
        // to reflect what error types should be reported. This means we should get 0 (no errors).
        $errorReportingLevel = error_reporting();
        $isSilenced = ($errorReportingLevel == 0);

        if (PHP_MAJOR_VERSION >= 8) {
            // In PHP 8+, some errors are unsilenceable, so we should respect that.
            if (in_array($level, self::PHP8_UNSILENCEABLE_ERRORS)) {
                $isSilenced = false;
            } else {
                // If an error is silenced, `error_reporting()` won't return 0,
                // but rather a bitmask of the unsilenceable errors.
                $unsilenceableErrorsBitmask = array_reduce(
                    self::PHP8_UNSILENCEABLE_ERRORS, function ($bitMask, $errLevel) {
                        return $bitMask | $errLevel;
                });
                $isSilenced = $errorReportingLevel === $unsilenceableErrorsBitmask;
            }
        }

        if ($isSilenced) {
            return false;
        }

        $this->honeybadger->notify(
            new ErrorException($error, 0, $level, $file, $line)
        );

        if (is_callable($this->previousHandler)) {
            call_user_func($this->previousHandler, $level, $error, $file, $line);
        }
    }
}
