<?php

namespace Honeybadger;

use \Honeybadger\Util\Arr;

/**
 * @package  Honeybadger
 */
class Honeybadger {

	// Library versions
	const VERSION    = '0.1.0';
	const JS_VERSION = '0.0.2';

	// Notifier constants
	const NOTIFIER_NAME = 'honeybadger-php';
	const NOTIFIER_URL  = 'https://github.com/gevans/honeybadger-php';
	const LOG_PREFIX    = '** [Honeybadger] ';

	/**
	 * @var  Sender  Object responding to `send_to_honeybadger`.
	 */
	public static $sender;

	/**
	 * @var  Config  Honeybadger configuration.
	 */
	public static $config;

	/**
	 * @var  Logger  Honeybadger logger.
	 */
	public static $logger;

	/**
	 * @var  array  Stores custom data for sending user-specific information
	 *              in notifications.
	 */
	protected static $context = array();

	/**
	 * @var  boolean  Whether Honeybadger has been initialized.
	 */
	protected static $_init;

	/**
	 * Initializes Honeybadger with a new global configuration.
	 *
	 * @return  void
	 */
	public static function init()
	{
		// Already initialized?
		if (self::$_init)
			return;

		// Honeybadger is now initialized.
		self::$_init = TRUE;

		self::$logger = new Logger\Void;
		self::$config = new Config;
		self::$sender = new Sender;
	}

	public static function context(array $data = array())
	{
		return self::$context = array_merge(self::$context, $data);
	}

	public static function reset_context(array $data = array())
	{
		self::$context = array();
		return self::context($data);
	}

	public static function handle_errors()
	{
		Error::register_handler();
		Exception::register_handler();
	}

	public static function report_environment_info()
	{
		self::$logger->add(self::$config->log_level, 'Environment info: :info', array(
			':info' => self::environment_info(),
		));
	}

	public static function report_response_body($response)
	{
		self::$logger->add(self::$config->log_level, "Response from Honeybadger:\n:response", array(
			':response' => $response,
		));
	}

	public static function environment_info()
	{
		$info = '[PHP: '.phpversion().']';

		if (self::$config->framework)
		{
			$info .= ' ['.self::$config->framework.']';
		}

		if (self::$config->environment_name)
		{
			$info .= ' [Env: '.self::$config->environment_name.']';
		}

		return $info;
	}

	public static function notify($exception, array $options = array())
	{
		$notice = self::build_notice_for($exception, $options);
		return self::send_notice(self::build_notice_for($exception, $options));
	}

	public static function notify_or_ignore($exception, array $options = array())
	{
		$notice = self::build_notice_for($exception, $options);

		if ( ! $notice->ignored)
		{
			return self::send_notice($notice);
		}
	}

	public static function build_lookup_hash_for($exception, array $options = array())
	{
		$notice = self::build_notice_for($exception, $options);

		$result = array(
			'action'           => $notice->action,
			'component'        => $notice->component,
			'environment_name' => 'production',
		);

		if ($notice->error_class)
		{
			$result['error_class'] = $notice->error_class;
		}

		if ($notice->backtrace->has_lines())
		{
			$result['file']        = $notice->backtrace->lines[0]->file;
			$result['line_number'] = $notice->backtrace->lines[0]->number;
		}

		return $result;
	}

	private static function send_notice($notice)
	{
		if (self::$config->is_public())
		{
			return $notice->deliver();
		}
	}

	private static function build_notice_for($exception, array $options = array())
	{
		if ($exception instanceof \Exception)
		{
			$options['exception'] = self::unwrap_exception($exception);
		}
		elseif (Arr::is_array($exception))
		{
			$options = Arr::merge($options, $exception);
		}

		return Notice::factory($options);
	}

	private static function unwrap_exception($exception)
	{
		if ($previous = $exception->getPrevious())
		{
			return self::unwrap_exception($previous);
		}

		return $exception;
	}

} // End Honeybadger

// Additional measure to ensure defaults are initialized.
Honeybadger::init();