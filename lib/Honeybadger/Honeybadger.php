<?php

namespace Honeybadger;

/**
 * @package  Honeybadger
 */
class Honeybadger {

	// Library version
	const VERSION = '0.1.0';

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
	 * @var  array  Stores custom data for sending user-specific information
	 *              in notifications.
	 */
	public static $context = array();

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

		self::$config = new Config;
	}

} // End Honeybadger

// Additional measure to ensure defaults are initialized.
Honeybadger::init();