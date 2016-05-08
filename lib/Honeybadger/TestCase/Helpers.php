<?php

namespace Honeybadger\TestCase;

use ReflectionClass;

/**
 * Collection of helper methods for use in unit tests.
 *
 * Ripped from [Kohana](http://kohanaframework.org/).
 *
 * @package   Honeybadger/Tests
 */
class Helpers
{

    /**
     * @var  boolean  Whether an internet connection is available.
     */
    protected static $has_internet = null;

    /**
     * @var  array  Collection of names of superglobals.
     */
    protected static $superglobals = [
        '_SERVER',
        '_GET',
        '_POST',
        '_FILES',
        '_COOKIE',
        '_SESSION',
        '_REQUEST',
        '_ENV',
    ];
    /**
     * @var  array  Backup of environment variables.
     */
    protected $environment_backup = [];

    /**
     * Checks for internet connectivity.
     *
     * @return  boolean  Whether an internet connection is available.
     */
    public static function hasInternet()
    {
        if (self::$has_internet === null) {
            // The @ operator is used here to avoid DNS errors
            // when there is no connection.
            $sock               = @fsockopen("www.google.com", 80, $errno, $errstr, 1);
            self::$has_internet = (boolean)$sock;
        }

        return self::$has_internet;
    }

    /**
     * Helper function which replaces forward slashes with
     * OS-specific delimiters.
     *
     * @param   string $path Path to replace slashes in.
     *
     * @return  string
     */
    public static function dirSeparator($path)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Restores the environment to its original state.
     *
     * @chainable
     * @return  $this
     */
    public function restoreEnvironment()
    {
        $this->setEnvironment($this->environment_backup);
    }

    /**
     * Allows easy setting and backing up of environment configurations.
     *
     * Option types are checked in the following order:
     *
     * * Server Var
     * * Static Variable
     * * Config option
     *
     * @param  $environment  Array of environment to set
     *
     * @return void
     */
    public function setEnvironment(array $environment = [])
    {
        if (!count($environment)) {
            return;
        }

        foreach ($environment as $option => $value) {
            $backup_needed = !array_key_exists($option, $this->environment_backup);

            // Handle changing superglobals
            if (in_array($option, self::$superglobals)) {
                // For some reason we need to do this in order to change the superglobals
                global $$option;

                if ($backup_needed) {
                    $this->environment_backup[$option] = $$option;
                }

                // PHPUnit makes a backup of superglobals automatically
                $$option = $value;
            } // If this is a static property i.e. Html::$windowed_urls
            elseif (strpos($option, '::$') !== false) {
                list($class, $var) = explode('::$', $option, 2);

                $class = new ReflectionClass($class);

                if ($backup_needed) {
                    $this->environment_backup[$option] = $class->getStaticPropertyValue($var);
                }

                $class->setStaticPropertyValue($var, $value);
            } // If this is an environment variable
            elseif (preg_match('/^[A-Z_-]+$/', $option) or isset($_SERVER[$option])) {
                if ($backup_needed) {
                    $this->environment_backup[$option] = isset($_SERVER[$option]) ? $_SERVER[$option] : '';
                }

                $_SERVER[$option] = $value;
            }
        }
    }
} // End Helpers
