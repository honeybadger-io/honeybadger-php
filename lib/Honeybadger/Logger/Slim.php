<?php

namespace Honeybadger\Logger;

use \Honeybadger\Logger;
use \Slim\Log;

/**
 * Writes log entries to a configured Slim application's logger.
 */
class Slim extends Logger {

	public function write($severity, $message = NULL)
	{
		$this->logger->write($message, $severity);
	}

	private function translate_severity($severity)
	{
		switch ($severity)
		{
			case self::FATAL:
				$severity = Log::FATAL;
			case self::ERROR:
				$severity = Log::ERROR;
			case self::WARN:
				$severity = Log::WARN;
			case self::INFO:
				$severity = Log::INFO;
			case self::DEBUG:
				$severity = Log::DEBUG;
				break;
		}

		return $severity;
	}

} // End Slim