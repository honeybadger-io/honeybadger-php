<?php
/**
 * Catch and notify of an exception you caught in your app.
 */

require 'config.php';

use Honeybadger\Honeybadger;

try
{
	throw new Exception('Oh noes! Something broke!');
}
catch (Exception $e)
{
	echo Honeybadger::notify_or_ignore($e);
}