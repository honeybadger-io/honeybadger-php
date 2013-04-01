<?php

namespace Honeybadger;

use \Honeybadger\Util\Arr;
use \Honeybadger\Util\SemiOpenStruct;

class Config extends SemiOpenStruct {

	protected $_attribute_methods = array('secure', 'log_level');

	/**
	 * @var  string  The API key for your project, found on the project edit form.
	 */
	public $api_key;

	/**
	 * @var  string  The host to connect to (defaults to `api.honeybadger.io`).
	 */
	public $host = 'api.honeybadger.io';

	/**
	 * @var  integer  The port on which your Honeybadger server runs (defaults
	 *       to `443` for secure connections, `80` for insecure connections).
	 */
	public $port;

	/**
	 * @var  boolean  `TRUE` for https connections, `FALSE` for http connections.
	 */
	protected $_secure = TRUE;

	/**
	 * @var  mixed  `'system'` to use whatever CAs OpenSSL has
	 *       installed on your system. `NULL` to use the ca-bundle.crt file
	 *       included in Honeybadger itself (recommended and default), or
	 *       specify a full path to a file or directory.
	 */
	protected $certificate_authority;

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
	 *       (if using a proxy).
	 */
	public $proxy_user;

	/**
	 * @var  string  The password to use hwen logging into your proxy server
	 *       (if using a proxy).
	 */
	public $proxy_pass;

	/**
	 * @var  array  A list of filters for cleaning and pruning the backtrace.
	 *       See [Config::filter_backtrace].
	 */
	public $backtrace_filters = array();

	/**
	 * @var  array  A list of parameters that should be filtered out of what is
	 *       sent to Honeybadger. By default, all `password` and
	 *       `password_confirmation` attributes will have their contents replaced.
	 */
	public $params_filters = array();

	/**
	 * @var  array  A list of filters for ignoring exceptions.
	 *       See [Config::ignore_by_filter].
	 */
	public $ignore_by_filters = array();

	/**
	 * @var  array  A list of exception classes to ignore.
	 */
	public $ignore = array();

	/**
	 * @var  array  A list of user agents to ignore.
	 */
	public $ignore_user_agents = array();

	/**
	 * @var  array  A list of environments in which notifications should not
	 *              be sent.
	 */
	public $development_environments = array(
		'development', 'testing',
	);

	/**
	 * @var  string  The name of the environment the application is running in.
	 */
	public $environment_name;

	/**
	 * @var  string  The path to the project in which the error occursed.
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
	 *       send notifications.
	 */
	public $notifier_version;

	/**
	 * @var  string  The url of the notifier library being used to
	 *               send notifications.
	 */
	public $notifier_url;

	/**
	 * @var  string  The text that the placeholder is replaced with. `{{error_id}}` is the actual error number.
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
	 * @var  boolean  `TRUE` to send session data, `FALSE` to exclude.
	 */
	public $send_request_session = TRUE;

	/**
	 * @var  boolean  `TRUE` to log extra debug info, `FALSE` to suppress.
	 */
	public $debug = FALSE;


	/**
	 * @var  array  Default filtered parameters.
	 */
	public static $default_params_filters = array(
		'password',
		'password_confirmation',
		'HTTP_AUTHORIZATION',
		'HTTP_PROXY_AUTHORIZATION',
		'PHP_AUTH_DIGEST',
		'PHP_AUTH_PW',
	);

	/**
	 * @var  array  Default backtrace filters.
	 */
	public static $default_backtrace_filters = array(
		array('\\Honeybadger\\Filter', 'project_root'),
		array('\\Honeybadger\\Filter', 'expand_paths'),
		array('\\Honeybadger\\Filter', 'honeybadger_paths'),
	);

	/**
	 * @var  array  Default ignored classes.
	 */
	public static $default_ignore = array();

	/**
	 * Instantiates a new configuration object, applies user-specified options,
	 * and sets defaults.
	 *
	 * @param  array  $config  User-specified configuration.
	 */
	public function __construct(array $config = array())
	{
		// Set default notifier info
		$this->notifier_name = Honeybadger::NOTIFIER_NAME;
		$this->notifier_version = Honeybadger::VERSION;
		$this->notifier_url = Honeybadger::NOTIFIER_URL;

		// Set user-specified configuration
		$this->values($config);

		// Merge in preconfigured defaults
		$this->params_filters    = Arr::merge($this->params_filters, self::$default_params_filters);
		$this->backtrace_filters = Arr::merge($this->backtrace_filters, self::$default_backtrace_filters);
		$this->ignore            = Arr::merge($this->ignore, self::$default_ignore);

		// FIXME: This feels very brittle...
		if ( ! $this->port)
		{
			$this->port = $this->default_port();
		}

		if ( ! $this->certificate_authority)
		{
			$this->certificate_authority = $this->default_certificate_authority();
		}
	}

	/**
	 * Sets configuration options for each supplied key-value pair.
	 *
	 * @param   array  $config  User-specified configuration.
	 * @return  $this
	 * @chainable
	 */
	public function values(array $config = array())
	{
		foreach ($config as $item => $value)
		{
			$this->set($item, $value);
		}

		return $this;
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
	 * @param   callback  $filter  The new backtrace filter
	 * @return  void
	 */
	public function filter_backtrace($filter)
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
	 *             return TRUE;
	 *         }
	 *     });
	 *
	 * [!!] If the callback returns `TRUE` the exception will be ignored,
	 * otherwise it will be processed by Honeybadger.
	 *
	 * @param   callback  $filter  The new ignore filter
	 * @return  void
	 */
	public function ignore_by_filter($filter)
	{
		$this->ignore_by_filters[] = $filter;
	}

	/**
	 * Overrides the list of ignored errors.
	 *
	 * @param   array  $names  A list of exceptions to ignore
	 * @return  void
	 */
	public function ignore_only(/* $name1, $name2, $name3, ... */)
	{
		$this->ignore = func_get_args();
	}

	/**
	 * Overrides the list of default ignored user agents.
	 *
	 * @param  array  $names  A list of user agents to ignore
	 */
	public function ignore_user_agents_only(/* $name1, $name2, $name3, ... */)
	{
		$this->ignore_user_agents = func_get_args();
	}

	/**
	 * Returns an array of all configurable options merged with `$config`.
	 * @param   array  $config  Options to merge with configuration
	 * @return  array
	 */
	public function merge(array $config = array())
	{
		return Arr::merge($this->as_array(), $config);
	}

	/**
	 * Determines if the notifier will send notices.
	 *
	 * @return  boolean  `FALSE` if in a development environment
	 */
	public function is_public()
	{
		return ( ! in_array($this->environment_name, $this->development_environments));
	}

	public function log_level()
	{
		return $this->debug ? Logger::INFO : Logger::DEBUG;
	}

	public function base_url()
	{
		$base  = $this->secure ? 'https' : 'http';
		$base .= '://'.$this->host;

		if (($this->secure AND $this->port != 443) OR ( ! $this->secure AND $this->port != 80))
		{
			$base .= ':'.$this->port;
		}

		return $base;
	}

	public function set($option, $value)
	{
		if ($option == 'secure')
			return $this->secure($value);

		$this->$option = $value;
	}

	public function offsetUnset($option)
	{
		$this->$option = NULL;
	}

	public function secure($value = NULL)
	{
		if ($value === NULL)
			return $this->_secure;

		$use_default = ($this->port === NULL OR !is_integer($this->port) OR
		                $this->port == $this->default_port());
		$this->_secure = $value;

		if ($use_default)
		{
			$this->port = $this->default_port();
		}
	}

	/**
	 * Determines a default port, depending on whether a secure connection is
	 * configured.
	 *
	 * @return  integer  Default port
	 */
	private function default_port()
	{
		return ($this->secure) ? 443 : 80;
	}

	/**
	 * Determines the path to the certificate authority bundled with
	 * the library.
	 *
	 * @return  string  Path to certificate authority bundle.
	 */
	private function default_certificate_authority()
	{
		return realpath(__DIR__.'/../../resources/ca-bundle.crt');
	}

} // End Config

// Additional measure to ensure defaults are initialized.
Honeybadger::init();