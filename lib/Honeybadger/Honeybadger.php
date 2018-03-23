<?php

namespace Honeybadger;

use Honeybadger\Util\Arr;
use Honeybadger\GuzzleFactory;

/**
 * @package  Honeybadger
 */
class Honeybadger
{
    // Library versions
    /**
     * API version
     */
    const VERSION = '0.3.1';
    /**
     * API name
     */
    const NOTIFIER_NAME = 'honeybadger-php';

    // Notifier constants
    /**
     * VCS location
     */
    const NOTIFIER_URL = 'https://github.com/honeybadger-io/honeybadger-php';
    /**
     * Entry prefix
     */
    const LOG_PREFIX = '** [Honeybadger] ';
    /**
     * @var  Sender  Object responding to `send_to_honeybadger`.
     */
    public static $sender;
    /**
     * @var  Config  Honeybadger configuration.
     */
    public static $config;
    /**
     * @var  Logger  Honeybadger logger.
     */
    public static $logger;
    /**
     * @var  array  Stores custom data for sending user-specific information
     *              in notifications.
     */
    protected static $context = [];
    /**
     * @var  boolean  Whether Honeybadger has been initialized.
     */
    protected static $init;

    /**
     * Initializes Honeybadger with a new global configuration.
     *
     * @return  void
     */
    public static function init()
    {
        // Already initialized?
        if (self::$init) {
            return;
        }

        // Honeybadger is now initialized.
        self::$init = true;

        self::$logger = new Logger\Standard;
        self::$config = new Config;
        self::$sender = new Sender(new GuzzleFactory);
    }

    /**
     * @return void
     */
    public static function registerExceptionHandler()
    {
        Exception::register_handler();
    }

    /**
     * @return void
     */
    public static function registerErrorHandler()
    {
        Error::register_handler();
    }

    /**
     * @return void
     */
    public static function registerGlobalHandlers()
    {
        self::registerErrorHandler();
        self::registerExceptionHandler();
    }

    /**
     * Merges supplied `$data` with current context. This can be anything,
     * such as user information.
     *
     * @param   array $data Data to add to the context.
     *
     * @return  array  The current context.
     */
    public static function context(array $data = [])
    {
        return self::$context = array_merge(self::$context, $data);
    }

    /**
     * Replaces the context with the supplied data. If no data is provided, the
     * context is emptied.
     *
     * @param   array $data Data to add to the context.
     *
     * @return  array  The current context.
     */
    public static function resetContext(array $data = [])
    {
        return self::$context = $data;
    }

    /**
     *
     */
    public static function reportEnvironmentInfo()
    {
        self::$logger->log(
            self::$config->logLevel,
            'Environment info: {info}',
            [
                'info' => self::environmentInfo(),
            ]
        );
    }

    /**
     * @return string
     */
    public static function environmentInfo()
    {
        $info = '[PHP: ' . phpversion() . ']';

        if (self::$config->framework) {
            $info .= ' [' . self::$config->framework . ']';
        }

        if (self::$config->environment_name) {
            $info .= ' [Env: ' . self::$config->environment_name . ']';
        }

        return $info;
    }

    /**
     * @param $response
     */
    public static function reportResponseBody($response)
    {
        self::$logger->log(
            self::$config->logLevel,
            "Response from Honeybadger:\n{response}",
            [
                'response' => $response,
            ]
        );
    }

    /**
     * Sends a notice with the supplied `$exception` and `$options`.
     *
     * @param   array|\Exception $exception The exception.
     * @param   array      $options   Additional options for the notice.
     *
     * @return  string     The error identifier.
     */
    public static function notify($exception, array $options = [])
    {
        $notice = self::buildNoticeFor($exception, $options);

        return self::sendNotice($notice);
    }

    /**
     * @param       $exception
     * @param array $options
     *
     * @return Notice
     */
    private static function buildNoticeFor($exception, array $options = [])
    {
        if ($exception instanceof \Exception) {
            $options['exception'] = self::unwrapException($exception);
        } elseif (Arr::isArray($exception)) {
            $options = Arr::merge($options, $exception);
        }

        return Notice::factory($options);
    }

    /**
     * @param $exception
     *
     * @return mixed
     */
    private static function unwrapException($exception)
    {
        if ($previous = $exception->getPrevious()) {
            return self::unwrapException($previous);
        }

        return $exception;
    }

    /**
     * @param $notice
     *
     * @return null
     */
    private static function sendNotice($notice)
    {
        if (self::$config->isPublic()) {
            return $notice->deliver();
        }

        return null;
    }

    /**
     * Sends a notice with the supplied `$exception` and `$options` if it is
     * not an ignored class or filtered.
     *
     * @param   \Exception $exception The exception.
     * @param   array      $options   Additional options for the notice.
     *
     * @return  string|null  The error identifier. `null` if skipped.
     */
    public static function notifyOrIgnore($exception, array $options = [])
    {
        $notice = self::buildNoticeFor($exception, $options);

        if (!$notice->isIgnored()) {
            return self::sendNotice($notice);
        }

        return null;
    }

    /**
     * @param       $exception
     * @param array $options
     *
     * @return array
     */
    public static function buildLookupHashFor($exception, array $options = [])
    {
        $notice = self::buildNoticeFor($exception, $options);

        $result = [
            'action'           => $notice->action,
            'component'        => $notice->component,
            'environment_name' => 'production',
        ];

        if ($notice->error_class) {
            $result['error_class'] = $notice->error_class;
        }

        if ($notice->backtrace->hasLines()) {
            $result['file']        = $notice->backtrace->lines[0]->file;
            $result['line_number'] = $notice->backtrace->lines[0]->number;
        }

        return $result;
    }
} // End Honeybadger

// Additional measure to ensure defaults are initialized.
Honeybadger::init();
