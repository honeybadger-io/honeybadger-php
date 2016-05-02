<?php

namespace Honeybadger;

use Honeybadger\Util\Arr;

/**
 * @package  Honeybadger
 */
class Honeybadger
{
    // Library versions
    const VERSION = '0.1.0';
    const JS_VERSION = '0.0.2';

    // Notifier constants
    const NOTIFIER_NAME = 'honeybadger-php';
    const NOTIFIER_URL = 'https://github.com/honeybadger-io/honeybadger-php';
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
    protected static $context = array();

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
        if (self::$init)
            return;

        // Honeybadger is now initialized.
        self::$init = true;

        self::$logger = new Logger\Void;
        self::$config = new Config;
        self::$sender = new Sender;

        // Set Honeybadger as the error and exception handler.
        self::handleErrors();
    }

    /**
     * Merges supplied `$data` with current context. This can be anything,
     * such as user information.
     *
     * @param   array $data Data to add to the context.
     * @return  array  The current context.
     */
    public static function context(array $data = array())
    {
        return self::$context = array_merge(self::$context, $data);
    }

    /**
     * Replaces the context with the supplied data. If no data is provided, the
     * context is emptied.
     *
     * @param   array $data Data to add to the context.
     * @return  array  The current context.
     */
    public static function resetContext(array $data = array())
    {
        return self::$context = $data;
    }

    /**
     * Registers Honeybadger as the global error and exception handler. Any
     * uncaught exceptions and errors will be sent to Honeybadger by default.
     *
     * @return  void
     */
    public static function handleErrors()
    {
        Error::register_handler();
        Exception::register_handler();
    }

    public static function reportEnvironmentInfo()
    {
        self::$logger->add(self::$config->logLevel,
            'Environment info: :info',
            array(
                ':info' => self::environmentInfo(),
            ));
    }

    public static function reportResponseBody($response)
    {
        self::$logger->add(
            self::$config->logLevel,
            "Response from Honeybadger:\n:response",
            array(
                ':response' => $response,
            )
        );
    }

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
     * Sends a notice with the supplied `$exception` and `$options`.
     *
     * @param   \Exception $exception The exception.
     * @param   array $options Additional options for the notice.
     * @return  string     The error identifier.
     */
    public static function notify($exception, array $options = array())
    {
        $notice = self::buildNoticeFor($exception, $options);
        return self::sendNotice($notice);
    }

    /**
     * Sends a notice with the supplied `$exception` and `$options` if it is
     * not an ignored class or filtered.
     *
     * @param   \Exception $exception The exception.
     * @param   array $options Additional options for the notice.
     * @return  string|null  The error identifier. `null` if skipped.
     */
    public static function notifyOrIgnore($exception,
                                          array $options = array())
    {
        $notice = self::buildNoticeFor($exception, $options);

        if (!$notice->isIgnored()) {
            return self::sendNotice($notice);
        }
    }

    public static function buildLookupHashFor($exception,
                                              array $options = array())
    {
        $notice = self::buildNoticeFor($exception, $options);

        $result = array(
            'action' => $notice->action,
            'component' => $notice->component,
            'environment_name' => 'production',
        );

        if ($notice->error_class) {
            $result['error_class'] = $notice->error_class;
        }

        if ($notice->backtrace->hasLines()) {
            $result['file'] = $notice->backtrace->lines[0]->file;
            $result['line_number'] = $notice->backtrace->lines[0]->number;
        }

        return $result;
    }

    private static function sendNotice($notice)
    {
        if (self::$config->isPublic()) {
            return $notice->deliver();
        }
    }

    private static function buildNoticeFor($exception,
                                           array $options = array())
    {
        if ($exception instanceof \Exception) {
            $options['exception'] = self::unwrapException($exception);
        } elseif (Arr::isArray($exception)) {
            $options = Arr::merge($options, $exception);
        }

        return Notice::factory($options);
    }

    private static function unwrapException($exception)
    {
        if ($previous = $exception->getPrevious()) {
            return self::unwrapException($previous);
        }

        return $exception;
    }

} // End Honeybadger
