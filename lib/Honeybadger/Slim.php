<?php

namespace Honeybadger;

use \Honeybadger\Honeybadger;
use \Honeybadger\Config;
use \Honeybadger\Util\Arr;

class Slim {

	/**
	 * Configures Honeybadger for the specified app with the supplied
	 * configuration options and adds middleware for exception catching.
	 *
	 * @param   Slim    $app      The Slim app.
	 * @param   array   $options  The config options.
	 * @return  Config  The Honeybadger configuration.
	 */
	public static function init(\Slim\Slim $app, array $options = array())
	{
		// Add missing, detected options.
		$options = Arr::merge(self::default_options($app), $options);

		if ($logger = $app->getLog())
		{
			// Wrap the application logger.
			Honeybadger::$logger = new \Honeybadger\Logger\Slim($app->getLog());
		}

		// Add our own middleware to the stack.
		$app->add(new Slim\Middleware\ExceptionCatcher);

		// Create a new configuration with the merged options.
		return Honeybadger::$config = new Config($options);
	}

	private static function default_options(\Slim\Slim $app)
	{
		return array(
			'environment_name' => $app->getMode(),
			'framework'        => sprintf('Slim %s', \Slim\Slim::VERSION),
		);
	}

} // End Slim