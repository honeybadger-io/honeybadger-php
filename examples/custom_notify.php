<?php
/**
 * Notify with custom options. See lib/Honeybadger/Notice.php for
 * available options.
 */

require 'config.php';

use Honeybadger\Honeybadger;

echo Honeybadger::notify(array(
	'error_class'   => 'Special Error',
	'error_message' => 'A custom error message.',
	'parameters'    => array(
		'action'   => 'index',
		'username' => 'somebody',
		'password' => 'something',
	),
));