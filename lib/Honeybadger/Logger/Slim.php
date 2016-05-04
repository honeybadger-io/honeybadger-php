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
        $this->logger->write($message, $this->translateSeverity($severity));
    }

    private function translateSeverity($severity)
    {
        switch ($severity) {
            case self::EMERGENCY:
                $severity = Log::EMERGENCY;
                break;

            case self::ALERT:
                $severity = Log::ALERT;
                break;

            case self::CRITICAL:
                $severity = Log::CRITICAL;
                break;

            case self::ERROR:
                $severity = Log::ERROR;
                break;

            case self::WARNING:
                $severity = Log::WARN;
                break;

            case self::NOTICE:
                $severity = Log::NOTICE;
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
