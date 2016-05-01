<?php

namespace Honeybadger\Logger;

use Honeybadger\Logger;

/**
 * Writes log entries to nowhere with the expectation that developers interested
 * in this library's messages will implement their own logger or use a
 * bundle/module already written for their framework or choice to replace this
 * default in [Honeybadger::$logger].
 *
 * TODO: Investigate to see if logging to STDERR by default is feasible.
 *
 * @package   Honeybadger
 * @category  Logging
 */
class Void extends Logger
{

    public function write($severity, $message = null)
    {
        // noop
    }

}
