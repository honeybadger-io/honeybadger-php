<?php
/**
 * When all else fails, capture *everything* that slips through.
 */

require 'config.php';

use Honeybadger\Honeybadger;

Honeybadger::handle_errors();

throw new Exception('We failed. :(');
