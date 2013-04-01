<?php
/**
 * Notify with custom options. See lib/Honeybadger/Notice.php for
 * available options.
 */

use Honeybadger\Honeybadger;

$options = include 'config.php';

Honeybadger::$config->values($options);

echo Honeybadger::notify(array(
	'error_class'   => 'Special Error',
	'error_message' => 'A custom error message.',
	'parameters'    => array(
		'action'   => 'index',
		'username' => 'somebody',
		'password' => 'something',
	),
));