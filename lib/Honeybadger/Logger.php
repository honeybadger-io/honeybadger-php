<?php

namespace Honeybadger;

use Honeybadger\Errors\NonExistentProperty;
use Honeybadger\Errors\ReadOnly;

use Psr\Log\LoggerInterface;
use Psr\Log\InvalidArgumentException;

/**
 * Abstract logger. Should be extended to add support for various frameworks and
 * libraries utilizing Honeybadger.
 *
 * @package   Honeybadger
 * @category  Logging
 */
abstract class Logger implements LoggerInterface
{
    /**
     * Detailed debug information
     */
    const DEBUG = 'debug';
    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    const INFO = 'info';
    /**
     * Uncommon events
     */
    const NOTICE = 'notice';
    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    const WARNING = 'warning';
    /**
     * Runtime errors
     */
    const ERROR = 'error';
    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    const CRITICAL = 'critical';
    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    const ALERT = 'alert';
    /**
     * Urgent alert.
     */
    const EMERGENCY = 'emergency';

    /**
     * Logging levels from syslog protocol defined in RFC 5424
     *
     * @var array $levels Logging levels
     */
    protected static $levels = array(
        self::DEBUG,
        self::INFO,
        self::NOTICE,
        self::WARNING,
        self::ERROR,
        self::CRITICAL,
        self::ALERT,
        self::EMERGENCY
    );

    /**
     * @var  object  The real logger.
     */
    protected $logger;

    /**
     * @var  integer  Severity threshold. Errors less severe than the threshold
     *                are silently ignored.
     */
    protected $threshold;

    /**
     * Initializes by wrapping the supplied logger to offer a consistent API,
     * allow Honeybadger to log messages without issues.
     *
     * @param  object $logger The *real* logger.
     * @param  string $threshold The lowest severity to log.
     */
    public function __construct($logger = null, $threshold = self::DEBUG)
    {
        $this->logger = $logger;
        $this->threshold = $threshold;
    }

    public function __get($key)
    {
        switch ($key) {
            case 'logger':
            case 'threshold':
                return $this->$key;
                break;
            default:
                throw new NonExistentProperty($this, $key);
                break;
        }
    }

    public function __set($key, $value)
    {
        throw new ReadOnly($this);
    }

    /**
     * Interpolates context values into the message placeholders.
     */
    private function interpolate($message, array $context = array())
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * Adds a new debug log entry with the supplied `$message`, replacing
     * with `$context`, if any. If the threshold is lower than
     * `Logger::DEBUG`, the message will be suppressed.
     *
     * @example
     *     $logger->debug('This message is {fruit}', array(
     *         'fruit' => 'bananas',
     *     ));
     *
     * Logs a message:
     *
     *     "** [Honeybadger] This message is bananas"
     *
     * @param   string $message The message to log.
     * @param   array $context Values to replace in message.
     * @return  $this
     * @chainable
     */
    public function debug($message = null, array $context = array())
    {
        return $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Adds a new info log entry with the supplied `$message`, replacing
     * with `$context`, if any. If the threshold is lower than
     * `Logger::INFO`, the message will be suppressed.
     *
     * @example
     *     $logger->info('This message is {fruit}', array(
     *         'fruit' => 'bananas',
     *     ));
     *
     * Logs a message:
     *
     *     "** [Honeybadger] This message is bananas"
     *
     * @param   string $message The message to log.
     * @param   array $context Values to replace in message.
     * @return  $this
     * @chainable
     */
    public function info($message = null, array $context = array())
    {
        return $this->log(self::INFO, $message, $context);
    }

    /**
     * Adds a new notice log entry with the supplied `$message`, replacing
     * with `$context`, if any. If the threshold is lower than
     * `Logger::NOTICE`, the message will be suppressed.
     *
     * @example
     *     $logger->error('This message is {fruit}', array(
     *         'fruit' => 'bananas',
     *     ));
     *
     * Logs a message:
     *
     *     "** [Honeybadger] This message is bananas"
     *
     * @param   string $message The message to log.
     * @param   array $context Values to replace in message.
     * @return  $this
     * @chainable
     */
    public function notice($message = null, array $context = array())
    {
        return $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Adds a new warning log entry with the supplied `$message`, replacing
     * with `$context`, if any. If the threshold is lower than
     * `Logger::WARNING`, the message will be suppressed.
     *
     * @example
     *     $logger->warning('This message is {fruit}', array(
     *         'fruit' => 'bananas',
     *     ));
     *
     * Logs a message:
     *
     *     "** [Honeybadger] This message is bananas"
     *
     * @param   string $message The message to log.
     * @param   array $context Values to replace in message.
     * @return  $this
     * @chainable
     */
    public function warning($message = null, array $context = array())
    {
        return $this->log(self::WARNING, $message, $context);
    }

    /**
     * Adds a new error log entry with the supplied `$message`, replacing
     * with `$context`, if any. If the threshold is lower than
     * `Logger::ERROR`, the message will be suppressed.
     *
     * @example
     *     $logger->error('This message is {fruit}', array(
     *         'fruit' => 'bananas',
     *     ));
     *
     * Logs a message:
     *
     *     "** [Honeybadger] This message is bananas"
     *
     * @param   string $message The message to log.
     * @param   array $context Values to replace in message.
     * @return  $this
     * @chainable
     */
    public function error($message = null, array $context = array())
    {
        return $this->log(self::ERROR, $message, $context);
    }

    /**
     * Adds a new critical log entry with the supplied `$message`, replacing
     * with `$context`, if any. If the threshold is lower than
     * `Logger::CRITICAL`, the message will be suppressed.
     *
     * @example
     *     $logger->critical('This message is {fruit}', array(
     *         'fruit' => 'bananas',
     *     ));
     *
     * Logs a message:
     *
     *     "** [Honeybadger] This message is bananas"
     *
     * @param   string $message The message to log.
     * @param   array $context Values to replace in message.
     * @return  $this
     * @chainable
     */
    public function critical($message = null, array $context = array())
    {
        return $this->log(self::ALERT, $message, $context);
    }

    /**
     * Adds a new alert log entry with the supplied `$message`, replacing
     * with `$context`, if any. If the threshold is lower than
     * `Logger::ALERT`, the message will be suppressed.
     *
     * @example
     *     $logger->alert('This message is {fruit}', array(
     *         'fruit' => 'bananas',
     *     ));
     *
     * Logs a message:
     *
     *     "** [Honeybadger] This message is bananas"
     *
     * @param   string $message The message to log.
     * @param   array $context Values to replace in message.
     * @return  $this
     * @chainable
     */
    public function alert($message = null, array $context = array())
    {
        return $this->log(self::ALERT, $message, $context);
    }

    /**
     * Adds a new emergency log entry with the supplied `$message`, replacing
     * with `$context`, if any. If the threshold is lower than
     * `Logger::EMERGENCY`, the message will be suppressed.
     *
     * @example
     *     $logger->emergency('This message is {fruit}', array(
     *         'fruit' => 'bananas',
     *     ));
     *
     * Logs a message:
     *
     *     "** [Honeybadger] This message is bananas"
     *
     * @param   string $message The message to log.
     * @param   array $context Values to replace in message.
     * @return  $this
     * @chainable
     */
    public function emergency($message = null, array $context = array())
    {
        return $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Writes the supplied `$message` to the logger with the supplied severity.
     * Subclasses must implement this method to handle the actual logging
     * of entries.
     *
     * @param   string $severity The severity of the message.
     * @param   string $message The message to log.
     * @return  $this
     * @chainable
     */
    abstract public function write($severity, $message = null);

    /**
     * Passes the supplied `$message` and `$severity` on to [Logger::write] if
     * `$severity` is within threshold.
     *
     * @param   string $severity The severity of the message.
     * @param   string $message The message to log.
     * @param   array $context Values to replace in message.
     * @return  $this
     * @chainable
     */
    public function log($severity, $message = null, array $context = array())
    {
        if (array_search($severity, static::$levels) >= array_search($this->threshold, static::$levels)) {
            $this->write($severity, $this->format($message, $context));
        }

        return $this;
    }

    /**
     * Replaces `$context` in `$message` and prefixes entries
     * with [Honeybadger::LOG_PREFIX].
     *
     * @example
     *     $log->format('Hello, {something}!', array(
     *         'something' => 'world',
     *     ));
     *     // => "** [Honeybadger] Hello, world!"
     *
     * @param   string $message The message to format.
     * @param   array $context Values to replace in message.
     * @return  string  The formatted message.
     */
    protected function format($message, array $context = array())
    {
        return Honeybadger::LOG_PREFIX . $this->interpolate($message, $context);
    }
} // End Logger
