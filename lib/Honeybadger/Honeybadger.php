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

} // End Honeybadger

// Additional measure to ensure defaults are initialized.
Honeybadger::init();