<?php
/**
 * Notify with custom options. See lib/Honeybadger/Notice.php for
 * available options.
 */

use Honeybadger\Honeybadger;

$options = include 'config.php';

Honeybadger::$config->values($options);

echo Honeybadger::notify(
    [
        'error_class'   => 'Special Error',
        'error_message' => 'A custom error message.',
        'parameters'    => [
            'action'   => 'index',
            'username' => 'somebody',
            'password' => 'something',
        ],
    ]
);
