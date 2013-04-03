<?php

namespace Honeybadger;

use \Honeybadger\Errors\NonExistentProperty;
use \Honeybadger\Errors\ReadOnly;

/**
 * Abstract logger. Should be extended to add support for various frameworks and
 * libraries utilizing Honeybadger.
 *
 * @package   Honeybadger
 * @category  Logging
 */
abstract class Logger {

	// Log levels
	const FATAL = 10;
	const ERROR = 20;
	const WARN  = 30;
	const INFO  = 40;
	const DEBUG = 50;

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
	 * @param  object   $logger     The *real* logger.
	 * @param  integer  $threshold  The lowest severity to log.
	 */
	public function __construct($logger = NULL, $threshold = self::DEBUG)
	{
		$this->logger    = $logger;
		$this->threshold = $threshold;
	}

	public function __get($key)
	{
		switch ($key)
		{
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
	 * Adds a new debug log entry with the supplied `$message`, replacing
	 * with `$variables`, if any. If the threshold is lower than
	 * `Logger::DEBUG`, the message will be suppressed.
	 *
	 * @example
	 *     $logger->debug('This message is :fruit', array(
	 *         ':fruit' => 'bananas',
	 *     ));
	 *
	 * Logs a message:
	 *
	 *     "** [Honeybadger] This message is bananans"
	 *
	 * @param   string  $message    The message to log.
	 * @param   array   $variables  Values to replace in message.
	 * @return  $this
	 * @chainable
	 */
	public function debug($message = NULL, array $variables = array())
	{
		return $this->add(self::DEBUG, $message);
	}

	/**
	 * Adds a new info log entry with the supplied `$message`, replacing
	 * with `$variables`, if any. If the threshold is lower than
	 * `Logger::INFO`, the message will be suppressed.
	 *
	 * @example
	 *     $logger->info('This message is :fruit', array(
	 *         ':fruit' => 'bananas',
	 *     ));
	 *
	 * Logs a message:
	 *
	 *     "** [Honeybadger] This message is bananans"
	 *
	 * @param   string  $message    The message to log.
	 * @param   array   $variables  Values to replace in message.
	 * @return  $this
	 * @chainable
	 */
	public function info($message = NULL, array $variables = array())
	{
		return $this->add(self::INFO, $message);
	}

	/**
	 * Adds a new warn log entry with the supplied `$message`, replacing
	 * with `$variables`, if any. If the threshold is lower than
	 * `Logger::WARN`, the message will be suppressed.
	 *
	 * @example
	 *     $logger->warn('This message is :fruit', array(
	 *         ':fruit' => 'bananas',
	 *     ));
	 *
	 * Logs a message:
	 *
	 *     "** [Honeybadger] This message is bananans"
	 *
	 * @param   string  $message    The message to log.
	 * @param   array   $variables  Values to replace in message.
	 * @return  $this
	 * @chainable
	 */
	public function warn($message = NULL, array $variables = array())
	{
		return $this->add(self::WARN, $message);
	}

	/**
	 * Adds a new error log entry with the supplied `$message`, replacing
	 * with `$variables`, if any. If the threshold is lower than
	 * `Logger::ERROR`, the message will be suppressed.
	 *
	 * @example
	 *     $logger->error('This message is :fruit', array(
	 *         ':fruit' => 'bananas',
	 *     ));
	 *
	 * Logs a message:
	 *
	 *     "** [Honeybadger] This message is bananans"
	 *
	 * @param   string  $message    The message to log.
	 * @param   array   $variables  Values to replace in message.
	 * @return  $this
	 * @chainable
	 */
	public function error($message = NULL, array $variables = array())
	{
		return $this->add(self::ERROR, $message);
	}

	/**
	 * Adds a new fatal log entry with the supplied `$message`, replacing
	 * with `$variables`, if any. If the threshold is lower than
	 * `Logger::FATAL`, the message will be suppressed.
	 *
	 * @example
	 *     $logger->fatal('This message is :fruit', array(
	 *         ':fruit' => 'bananas',
	 *     ));
	 *
	 * Logs a message:
	 *
	 *     "** [Honeybadger] This message is bananans"
	 *
	 * @param   string  $message    The message to log.
	 * @param   array   $variables  Values to replace in message.
	 * @return  $this
	 * @chainable
	 */
	public function fatal($message = NULL, array $variables = array())
	{
		return $this->add(self::FATAL, $message);
	}

	/**
	 * Writes the supplied `$message` to the logger with the supplied severity.
	 * Subclasses must implement this method to handle the actual logging
	 * of entries.
	 *
	 * @param   integer  $severity  The severity of the message.
	 * @param   string   $message   The message to log.
	 * @return  $this
	 * @chainable
	 */
	abstract public function write($severity, $message = NULL);

	/**
	 * Passes the supplied `$message` and `$severity` on to [Logger::write] if
	 * `$severity` is within threshold.
	 *
	 * @param   integer  $severity  The severity of the message.
	 * @param   string   $message   The message to log.
	 * @return  $this
	 * @chainable
	 */
	public function add($severity, $message = NULL, array $variables = array())
	{
		if ($severity <= $this->threshold)
		{
			$this->write($severity, $this->format($message, $variables));
		}

		return $this;
	}

	/**
	 * Replaces `$variables` in `$message` and prefixes entries
	 * with [Honeybadger::LOG_PREFIX].
	 *
	 * @example
	 *     $log->format('Hello, :something!', array(
	 *         ':something' => 'world',
	 *     ));
	 *     // => "** [Honeybadger] Hello, world!"
	 *
	 * @param   string  $message    The message to format.
	 * @param   array   $variables  Values to replace in message.
	 * @return  string  The formatted message.
	 */
	protected function format($message, array $variables = array())
	{
		return Honeybadger::LOG_PREFIX.strtr($message, $variables);
	}

} // End Logger