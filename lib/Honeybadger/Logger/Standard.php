<?php

namespace Honeybadger\Logger;

use Honeybadger\Logger;

/**
 * Writes log entries to PHP error_log with the expectation that developers
 * interested in this library's messages will implement their own logger or use
 * a bundle/module already written for their framework or choice to replace
 * this default in [Honeybadger::$logger].
 *
 * @package   Honeybadger
 * @category  Logging
 */
class Standard extends Logger
{
    /**
     * @param string      $severity
     * @param string|null $message
     *
     * @return void
     */
    public function write($severity, $message = null)
    {
        error_log($severity . ': ' . $message);
    }
}
