<?php

namespace Honeybadger\Logger;

use Honeybadger\Logger;

/**
 * Logger used in unit tests.
 *
 * @package   Honeybadger/Tests
 * @category  Logging
 */
class Test extends Logger
{

    /**
     * @var array
     */
    public $entries = [];

    /**
     * @param string      $severity
     * @param string|null $message
     *
     * @return void
     */
    public function write($severity, $message = null)
    {
        $this->entries[] = [
            'severity' => $severity,
            'message'  => $message,
        ];
    }

    /**
     * @return array
     */
    public function lastEntry()
    {
        return end($this->entries);
    }
} // End Test
