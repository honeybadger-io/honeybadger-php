<?php

/**
 * Catch and notify of an exception you caught in your app.
 */

use Honeybadger\Honeybadger;

$options = include 'config.php';

Honeybadger::$config->values($options);

try {
    throw new \Exception('Oh noes! Something broke!');
} catch (\Exception $e) {
    echo Honeybadger::notifyOrIgnore($e);
}
