<?php

namespace Honeybadger\Logger;

use \Honeybadger\Logger;

/**
 * Logger used in unit tests.
 */
class Test extends Logger {

	public $entries = array();

	public function write($severity, $message = NULL)
	{
		$this->entries[] = array(
			'severity' => $severity,
			'message'  => $message,
		);
	}

	public function last_entry()
	{
		return end($this->entries);
	}

} // End Test