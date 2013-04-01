<?php
/**
 * When all else fails, capture *everything* else that slips through.
 */

require 'config.php';

use Honeybadger\Honeybadger;

Honeybadger::handle_errors();

echo $foo;
