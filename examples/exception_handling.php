<?php
/**
 * When all else fails, capture *everything* that slips through.
 */

use Honeybadger\Honeybadger;

$options = include 'config.php';

Honeybadger::$config->values($options);

Honeybadger::handle_errors();

throw new Exception('We failed. :(');
