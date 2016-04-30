<?php

namespace Honeybadger\Logger;

use Honeybadger\Logger;
use Slim\Log;

/**
 * Writes log entries to a configured Slim application's logger.
 *
 * @package   Honeybadger/Integrations
 * @category  Slim
 */
class Slim extends Logger
{

    public function write($severity, $message = null)
    {
        $this->logger->write($message, $this->translate_severity($severity));
    }

    private function translate_severity($severity)
    {
        switch ($severity) {
            case self::FATAL:
                $severity = Log::FATAL;
                break;

            case self::ERROR:
                $severity = Log::ERROR;
                break;

            case self::WARN:
                $severity = Log::WARN;
                break;

            case self::INFO:
                $severity = Log::INFO;
                break;

            case self::DEBUG:
                $severity = Log::DEBUG;
                break;
        }

        return $severity;
    }

} // End Slim
