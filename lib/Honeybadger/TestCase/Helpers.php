<?php

namespace Honeybadger\TestCase;

use \ReflectionClass;

/**
 * Collection of helper methods for use in unit tests.
 *
 * Ripped from [Kohana](http://kohanaframework.org/).
 */
class Helpers {

	/**
	 * @var  boolean  Whether an internet connection is available.
	 */
	protected static $has_internet = NULL;

	/**
	 * @var  array  Collection of names of superglobals.
	 */
	protected static $superglobals = array(
		'_SERVER', '_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_REQUEST',
		'_ENV',
	);

	/**
	 * Checks for internet connectivity.
	 *
	 * @return  boolean  Whether an internet connection is available.
	 */
	public static function has_internet()
	{
		if (self::$has_internet === NULL)
		{
			// The @ operator is used here to avoid DNS errors when there is no connection.
			$sock = @fsockopen("www.google.com", 80, $errno, $errstr, 1);
			self::$has_internet = (boolean) $sock;
		}

		return self::$has_internet;
	}

	/**
	 * Helper function which replaces foward slashes with
	 * OS-specific delimiters.
	 *
	 * @param   string  $path  Path to replace slashes in.
	 * @return  string
	 */
	public static function dir_separator($path)
	{
		return str_replace('/', DIRECTORY_SEPARATOR, $path);
	}

	/**
	 * @var  array  Backup of environment variables.
	 */
	protected $environment_backup = array();

	/**
	 * Allows easy setting and backing up of enviroment configurations.
	 *
	 * Option types are checked in the following order:
	 *
	 * * Server Var
	 * * Static Variable
	 * * Config option
	 *
	 * @param  array  $environment  List of environment to set
	 */
	public function set_environment(array $environment = array())
	{
		if ( ! count($environment))
			return FALSE;

		foreach ($environment as $option => $value)
		{
			$backup_needed = ! array_key_exists($option, $this->environment_backup);

			// Handle changing superglobals
			if (in_array($option, self::$superglobals))
			{
				// For some reason we need to do this in order to change the superglobals
				global $$option;

				if ($backup_needed)
				{
					$this->environment_backup[$option] = $$option;
				}

				// PHPUnit makes a backup of superglobals automatically
				$$option = $value;
			}
			// If this is a static property i.e. Html::$windowed_urls
			elseif (strpos($option, '::$') !== FALSE)
			{
				list($class, $var) = explode('::$', $option, 2);

				$class = new ReflectionClass($class);

				if ($backup_needed)
				{
					$this->environment_backup[$option] = $class->getStaticPropertyValue($var);
				}

				$class->setStaticPropertyValue($var, $value);
			}
			// If this is an environment variable
			elseif (preg_match('/^[A-Z_-]+$/', $option) OR isset($_SERVER[$option]))
			{
				if ($backup_needed)
				{
					$this->environment_backup[$option] = isset($_SERVER[$option]) ? $_SERVER[$option] : '';
				}

				$_SERVER[$option] = $value;
			}
		}
	}

	/**
	 * Restores the environment to its original state.
	 *
	 * @chainable
	 * @return  $this
	 */
	public function restore_environment()
	{
		$this->set_environment($this->environment_backup);
	}

} // End Helpers