<?php

namespace Honeybadger\Errors;

/**
 * A generic exception with helpers for formatting messages and
 * string conversion.
 *
 * @package   Honeybadger
 * @category  Errors
 */
class HoneybadgerError extends \Exception
{

    /**
     * Creates a new exception.
     *
     *     throw new HoneybadgerError('Something went terribly wrong, :user',
     *         array(':user' => $user));
     *
     * @param   string $message error message
     * @param   array $variables translation variables
     * @param   integer|string $code the exception code
     * @param   \Exception $previous Previous exception
     */
    public function __construct(
        $message = '',
        array $variables = array(),
        $code = 0,
        \Exception $previous = null
    )
    {
        // Set the message
        $message = strtr($message, $variables);

        // Pass the message and integer code to the parent
        parent::__construct($message, (int)$code, $previous);

        // Save the unmodified code
        // @link http://bugs.php.net/39615
        $this->code = $code;
    }

    /**
     * Magic object-to-string method.
     *
     *     echo $exception;
     *
     * @uses    HoneybadgerError::text
     * @return  string
     */
    public function __toString()
    {
        return self::text($this);
    }

    /**
     * Get a single line of text representing the exception:
     *
     * Error [ Code ]: Message ~ File [ Line ]
     *
     * @param   \Exception $e
     * @return  string
     */
    public static function text(\Exception $e)
    {
        return sprintf('%s [ %s ]: %s', get_class($e), $e->getCode(),
            strip_tags($e->getMessage()));
    }

} // End HoneybadgerError
