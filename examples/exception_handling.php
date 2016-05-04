<?php
/**
 * When all else fails, capture *everything* that slips through.
 */

use Honeybadger\Honeybadger;

$options = include 'config.php';

Honeybadger::$config->values($options);

Honeybadger::handleErrors();

throw new Exception('We failed. :(');
