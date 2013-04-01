<?php require __DIR__.'/../vendor/autoload.php';
/**
 * Copy or rename this file to `config.php` and update the values to your
 * your liking.
 */

use Honeybadger\Honeybadger;

// See lib/Honeybadger/Config for configuration options.
Honeybadger::$config->values(array(
	'api_key'           => 'change me!',
	'http_open_timeout' => 15,
	'http_read_timeout' => 15,
	'environment_name'  => 'examples',
	'debug'             => TRUE,
));