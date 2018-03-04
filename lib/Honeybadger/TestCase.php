<?php

namespace Honeybadger;

use Honeybadger\TestCase\Helpers;
use Honeybadger\Util\Arr;
use Honeybadger\GuzzleFactory;

/**
 * Ripped from [Kohana](http://kohanaframework.org/).
 *
 * @package  Honeybadger/Tests
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Make sure PHPUnit backs up globals
     * @var boolean
     */
    protected $backupGlobals = false;

    /**
     * A set of unittest helpers that are shared between normal / database
     * testcases
     * @var Kohana_Unittest_Helpers
     */
    protected $_helpers;

    /**
     * A default set of environment to be applied before each test
     * @var array
     */
    protected $_environment_default = [];

    protected $_default_config = [];

    public function buildException(array $options = [])
    {
        if (!Arr::get($options, 'message')) {
            $options['message'] = \Phaker::lorem()->sentence;
            $options['code']    = rand(0, 999999);
        }

        return new \Exception($options['message'], $options['code']);
    }

    /**
     * Creates a predefined environment using the default environment
     *
     * Extending classes that have their own setUp() should call
     * parent::setUp()
     */
    public function setUp()
    {
        $this->_helpers = new Helpers;

        if (!isset($this->_environment_default['\\Honeybadger\\Honeybadger::$config'])) {
            $apiKey = Arr::get($_SERVER, 'HONEYBADGER_API_KEY');

            $config = new Config(
                Arr::merge(
                    [
                        'project_root'     => realpath(__DIR__ . '/../..'),
                        'framework'        => 'PHPUnit',
                        'environment_name' => 'testing',
                        'api_key'          => empty($apiKey) ? null : $apiKey,
                    ],
                    $this->_default_config
                )
            );

            $this->_environment_default['\\Honeybadger\\Honeybadger::$config'] = $config;
        }

        if (!isset($this->_environment_default['\\Honeybadger\\Honeybadger::$sender'])) {
            $this->_environment_default['\\Honeybadger\\Honeybadger::$sender'] = new Sender(new GuzzleFactory);
        }

        if (!isset($this->_environment_default['\\Honeybadger\\Honeybadger::$logger'])) {
            $this->_environment_default['\\Honeybadger\\Honeybadger::$logger'] = new Logger\Test;
        }

        $this->setEnvironment($this->_environment_default);
    }

    /**
     * Allows easy setting & backing up of environment config
     *
     * Option types are checked in the following order:
     *
     * * Server Var
     * * Static Variable
     *
     * @param array $environment List of environment to set
     */
    public function setEnvironment(array $environment)
    {
        return $this->_helpers->setEnvironment($environment);
    }

    /**
     * Restores the original environment overridden with setEnvironment()
     *
     * Extending classes that have their own tearDown()
     * should call parent::tearDown()
     */
    public function tearDown()
    {
        $this->_helpers->restoreEnvironment();
    }

    /**
     * Helper function that replaces all occurrences of '/' with
     * the OS-specific directory separator
     *
     * @param string $path The path to act on
     *
     * @return string
     */
    public function dirSeparator($path)
    {
        return Helpers::dirSeparator($path);
    }

    public function restoreEnvironment()
    {
        $this->_helpers->restoreEnvironment();
    }

    /**
     * Checks for internet connectivity.
     *
     * @return  boolean  Whether an internet connection is available.
     */
    public function hasInternet()
    {
        return Helpers::hasInternet();
    }
} // End TestCase
