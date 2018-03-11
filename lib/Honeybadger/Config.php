<?php

namespace Honeybadger;

use Honeybadger\Util\Arr;
use Honeybadger\Util\SemiOpenStruct;

/**
 * Stores Honeybadger configuration options. Accessible through
 * `Honeybadger::$config`.
 *
 * @package  Honeybadger
 */
class Config extends SemiOpenStruct
{

    /**
     * @var  array  Default filtered parameters.
     */
    public static $default_params_filters = [
        'password',
        'password_confirmation',
        'HTTP_AUTHORIZATION',
        'HTTP_PROXY_AUTHORIZATION',
        'PHP_AUTH_DIGEST',
        'PHP_AUTH_PW',
    ];
    /**
     * @var  array  Default backtrace filters.
     */
    public static $default_backtrace_filters = [
        ['\\Honeybadger\\Filter', 'projectRoot'],
        ['\\Honeybadger\\Filter', 'expandPaths'],
        ['\\Honeybadger\\Filter', 'honeybadgerPaths'],
    ];
    /**
     * @var  array  Default ignored classes.
     */
    public static $default_ignore = [];
    /**
     * @var  string  The API key for your project,
     *              found on the project edit form.
     */
    public $api_key;
    /**
     * @var  string  The host to connect to (defaults to `api.honeybadger.io`).
     */
    public $host = 'api.honeybadger.io';
    /**
     * @var  integer  The port on which your Honeybadger server runs (defaults
     *                to `443` for secure connections, `80` for
     *                insecure connections).
     */
    public $port;
    /**
     * @var  integer  The HTTP open timeout in seconds (defaults to `2`).
     */
    public $http_open_timeout = 2;

    /**
     * @var  integer  The HTTP read timeout in seconds (defaults to `5`).
     */
    public $http_read_timeout = 5;

    /**
     * @var  string  The hostname of your proxy server (if using a proxy).
     */
    public $proxy_host;

    /**
     * @var  integer  The port of your proxy server (if using a proxy).
     */
    public $proxy_port;

    /**
     * @var  string  The username to use when logging into your proxy server
     *               (if using a proxy).
     */
    public $proxy_user;

    /**
     * @var  string  The password to use when logging into your proxy server
     *               (if using a proxy).
     */
    public $proxy_pass;

    /**
     * @var  array  A list of filters for cleaning and pruning the backtrace.
     *              See [Config::filter_backtrace].
     */
    public $backtrace_filters = [];

    /**
     * @var  array  A list of parameters that should be filtered out of what is
     *              sent to Honeybadger. By default, all `password` and
     *              `password_confirmation` attributes will have their contents
     *              replaced.
     */
    public $params_filters = [];

    /**
     * @var  array  A list of filters for ignoring exceptions.
     *              See [Config::ignore_by_filter].
     */
    public $ignore_by_filters = [];

    /**
     * @var  array  A list of exception classes to ignore.
     */
    public $ignore = [];

    /**
     * @var  array  A list of user agents to ignore.
     */
    public $ignore_user_agents = [];

    /**
     * @var  array  A list of environments in which notifications should not
     *              be sent.
     */
    public $development_environments = [
        'development', 'testing',
    ];

    /**
     * @var  string  The name of the environment the application is running in.
     */
    public $environment_name;

    /**
     * @var  string  The path to the project in which the error occurred.
     *               TODO: Default to current working directory?
     */
    public $project_root;

    /**
     * @var  string  The name of the notifier library being used to
     *               send notifications.
     */
    public $notifier_name;

    /**
     * @var  string  The version of the notifier library being used to
     *               send notifications.
     */
    public $notifier_version;

    /**
     * @var  string  The url of the notifier library being used to
     *               send notifications.
     */
    public $notifier_url;

    /**
     * @var  string  The text that the placeholder is replaced with.
     *               `{{error_id}}` is the actual error number.
     */
    public $user_information = 'Honeybadger Error {{error_id}}';

    /**
     * @var  string  The framework Honeybadger is configured to use.
     */
    public $framework = 'Standalone';

    /**
     * @var  integer  The radius around trace line to include in source excerpt.
     */
    public $source_extract_radius = 2;

    /**
     * @var  boolean  `true` to send session data, `false` to exclude.
     */
    public $send_request_session = true;

    /**
     * @var  boolean  `true` to log extra debug info, `false` to suppress.
     */
    public $debug = false;
    /**
     * @var array
     */
    protected $attribute_methods = ['secure', 'logLevel'];
    /**
     * @var  boolean  `true` for https connections, `false` for http
     *       connections.
     */
    protected $_secure = true;
    /**
     * @var  mixed  `system` to use whatever CAs OpenSSL has installed on your
     *              system. `null` to use the ca-bundle.crt file included in
     *              Honeybadger itself (recommended and default), or specify a
     *              full path to a file or directory.
     */
    protected $certificate_authority;

    /**
     * Instantiates a new configuration object, applies user-specified options,
     * and sets defaults.
     *
     * @param  array $config User-specified configuration.
     */
    public function __construct(array $config = [])
    {
        // Set default notifier info
        $this->notifier_name    = Honeybadger::NOTIFIER_NAME;
        $this->notifier_version = Honeybadger::VERSION;
        $this->notifier_url     = Honeybadger::NOTIFIER_URL;

        $this->filteredHttpEnviromentKeys = [];

        // Read config from environment variables
        $this->api_key = getenv('HONEYBADGER_API_KEY');

        // Set user-specified configuration
        $this->values($config);

        // Merge in preconfigured defaults
        $this->params_filters = array_merge(
            $this->params_filters,
            self::$default_params_filters
        );

        $this->backtrace_filters = array_merge(
            $this->backtrace_filters,
            self::$default_backtrace_filters
        );

        $this->ignore = array_merge($this->ignore, self::$default_ignore);

        // FIXME: This feels very brittle...
        if (!$this->port) {
            $this->port = $this->defaultPort();
        }

        if (!$this->certificate_authority) {
            $this->certificate_authority = $this->defaultCertificateAuthority();
        }
    }

    /**
     * Sets configuration options for each supplied key-value pair.
     *
     * @param   array $config User-specified configuration.
     *
     * @return  $this
     * @chainable
     */
    public function values(array $config = [])
    {
        foreach ($config as $item => $value) {
            $this->set($item, $value);
        }

        return $this;
    }

    /**
     * Replaces the supplied `$option` with the supplied `$value`. Used to
     * change configuration options through `__get` and `__set` but may be used
     * directly.
     *
     *     $config->set('api_key', '12345')
     *            ->set('secure', true);
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return $this
     * @chainable
     */
    public function set($option, $value)
    {
        if ($option == 'secure') {
            return $this->secure($value);
        }

        $this->$option = $value;

        return $this;
    }

    /**
     * When `$value is `null`, the current configured `secure` option is
     * returned. Otherwise, `secure` is set to the supplied `$value`.
     *
     * @param   boolean $value The value to set.
     *
     * @return  boolean|$this  The value or configuration object.
     * @chainable
     */
    public function secure($value = null)
    {
        if ($value === null) {
            return $this->_secure;
        }

        $use_default   = ($this->port === null or !is_integer($this->port) or
            $this->port == $this->defaultPort());
        $this->_secure = $value;

        if ($use_default) {
            $this->port = $this->defaultPort();
        }

        return $this;
    }

    /**
     * Determines a default port, depending on whether a secure connection is
     * configured.
     *
     * @return  integer  Default port
     */
    private function defaultPort()
    {
        return $this->isSecure() ? 443 : 80;
    }

    /**
     * Alias for `secure`.
     *
     * @param   boolean $value The value to set.
     *
     * @return  boolean|$this  The value or configuration object.
     * @chainable
     */
    public function isSecure($value = null)
    {
        return $this->secure($value);
    }

    /**
     * Determines the path to the certificate authority bundled with
     * the library.
     *
     * @return  string  Path to certificate authority bundle.
     */
    private function defaultCertificateAuthority()
    {
        return realpath(__DIR__ . '/../../resources/ca-bundle.crt');
    }

    /**
     * Takes a callback and adds it to the list of backtrace filters. When the
     * filters run, the callback will be handed each line of the backtrace and
     * can modify it as necessary.
     *
     *     // Callback style
     *     Honeybadger::$config->filter_backtrace('strrev');
     *
     *     // Closure style
     *     Honeybadger::$config->filter_backtrace(function($line) {
     *         return preg_replace('/^'.APPPATH.'/', '[APPPATH]', $line);
     *     });
     *
     * @param   callback $filter The new backtrace filter
     *
     * @return  void
     */
    public function filterBacktrace($filter)
    {
        $this->backtrace_filters[] = $filter;
    }

    /**
     * Takes a callback and adds it to the list of ignore filters. When the
     * filters run, the callback will be handed the exception.
     *
     *     // Callback style
     *     Honeybadger::$config->ignore_by_filter('Some_Class::some_method');
     *
     *     // Closure style
     *     Honeybadger::$config->ignore_by_filter(function ($exception_data) {
     *         if ($exception_data['error_class'] == 'ORM_Validation_Exception')
     *         {
     *             return true;
     *         }
     *     });
     *
     * [!!] If the callback returns `true` the exception will be ignored,
     * otherwise it will be processed by Honeybadger.
     *
     * @param   callback $filter The new ignore filter
     *
     * @return  void
     */
    public function ignoreByFilter($filter)
    {
        $this->ignore_by_filters[] = $filter;
    }

    /**
     * Overrides the list of ignored errors.
     *
     * @param   array $names A list of exceptions to ignore
     *
     * @return  void
     */
    public function ignoreOnly($names)
    {
        $this->ignore = func_get_args();
    }

    /**
     * Overrides the list of default ignored user agents.
     *
     * @param  array $names A list of user agents to ignore
     */
    public function ignoreUserAgentsOnly($names)
    {
        $this->ignore_user_agents = func_get_args();
    }

    /**
     * Returns an array of all configurable options merged with `$config`.
     *
     * @param   array $config Options to merge with configuration
     *
     * @return  array  The merged configuration.
     */
    public function merge(array $config = [])
    {
        return Arr::merge($this->asArray(), $config);
    }

    /**
     * Determines whether the notifier will send notices.
     *
     * @return  boolean  `false` if in a development environment
     */
    public function isPublic()
    {
        return (!in_array(
            $this->environment_name,
            $this->development_environments
        ));
    }

    /**
     * @return integer `Logger::INFO` when `debug` is `true`, otherwise
     *                 `Logger::DEBUG`.
     *
     * @var  boolean  The detected log level.
     */
    public function logLevel()
    {
        return $this->debug ? Logger::INFO : Logger::DEBUG;
    }

    /**
     * Returns the base URL for Honeybadger's API endpoint.
     *
     * @return  string  The base URL.
     */
    public function baseUrl()
    {
        $base = $this->secure() ? 'https' : 'http';
        $base .= '://' . $this->host;

        if (($this->secure() and $this->port != 443)
            or (!$this->secure() and $this->port != 80)
        ) {
            $base .= ':' . $this->port;
        }

        return $base;
    }

    /**
     * @param mixed $option
     */
    public function offsetUnset($option)
    {
        $this->$option = null;
    }
} // End Config

// Additional measure to ensure defaults are initialized.
Honeybadger::init();
