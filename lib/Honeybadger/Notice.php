<?php

namespace Honeybadger;

use \Honeybadger\Backtrace;
use \Honeybadger\Filter;
use \Honeybadger\Errors\HoneybadgerError;
use \Honeybadger\Util\SemiOpenStruct;
use \Honeybadger\Util\Arr;

class Notice extends SemiOpenStruct {

	/**
	 * @var  array  The currently processing `Notice`.
	 */
	public static $current;

	/**
	 * Constructs and returns a new `Notice` with supplied options merged with
	 * [Honeybadger::$config].
	 *
	 * @params  array   $options  Notice options.
	 * @return  Notice  The constructed notice.
	 */
	public static function factory(array $options = array())
	{
		return new self(Honeybadger::$config->merge($options));
	}

	protected $_attribute_methods = array(
		'ignored',
	);

	/**
	 * @var  array  Original arguments passed to constructor.
	 */
	protected $args = array();

	/**
	 * @var  Exception  The exception that caused this notice, if any.
	 */
	protected $exception;

	/**
	 * @var  Backtrace  The backtrace from the given exception or hash.
	 */
	protected $backtrace;

	/**
	 * @var  string  The name of the class of error (such as `Exception`).
	 */
	protected $error_class;

	/**
	 * @var  string  Excerpt from source file.
	 */
	protected $source_extract;

	/**
	 * @var  integer  The number of lines of context to include before and after
	 *                source excerpt.
	 */
	protected $source_extract_radius = 2;

	/**
	 * @var  string  The name of the server environment (such as `production`).
	 */
	protected $environment_name;

	/**
	 * @var  array  CGI variables such as `REQUEST_METHOD`.
	 */
	protected $cgi_data = array();

	/**
	 * @var  string  The message from the exception, or a general description of
	 *               the error.
	 */
	protected $error_message;

	/**
	 * @var  boolean  See Config#send_request_session.
	 */
	protected $send_request_session;

	/**
	 * @var  array  See Config#backtrace_filters
	 */
	protected $backtrace_filters = array();

	/**
	 * @var  array  See Config#params_filters.
	 */
	protected $params_filters = array();

	/**
	 * @var  array  Parameters from the query string or request body.
	 */
	protected $params = array();

	/**
	 * @var  string  The component (if any) which was used in this request
	 *               (usually the controller).
	 */
	protected $component;

	/**
	 * @var  string  The action (if any) that was called in this request.
	 */
	protected $action;

	/**
	 * @var  array  Session data from the request.
	 */
	protected $session_data = array();

	/**
	 * @var  array  Additional contextual information (custom data).
	 */
	protected $context = array();

	/**
	 * @var  string  The path to the project that caused the error.
	 */
	protected $project_root;

	/**
	 * @var  string  The URL at which the error occurred (if any).
	 */
	protected $url;

	/**
	 * @var  array  See Config#ignore.
	 */
	protected $ignore = array();

	/**
	 * @var  array  See Config#ignore_by_filters.
	 */
	protected $ignore_by_filters = array();

	/**
	 * @var  string  The name of the notifier library sending this notice,
	 *               such as "Honeybadger Notifier".
	 */
	protected $notifier_name;

	/**
	 * @var  string  The version number of the notifier library sending this
	 *               notice, such as "2.1.3".
	 */
	protected $notifier_version;

	/**
	 * @var  string  A URL for more information about the notifier library
	 *               sending this notice.
	 */
	protected $notifier_url;

	/**
	 * @var  string  The host name where this error occurred (if any).
	 */
	protected $hostname;

	public function __construct(array $args = array())
	{
		// Store self to allow access in callbacks.
		self::$current = $this;

		$this->args = $args;

		$this->cgi_data         = Environment::factory(Arr::get($args, 'cgi_data'));
		$this->project_root     = Arr::get($args, 'project_root');
		$this->url              = Arr::get($args, 'url', $this->cgi_data['url']);
		$this->environment_name = Arr::get($args, 'environment_name');

		$this->notifier_name    = Arr::get($args, 'notifier_name');
		$this->notifier_version = Arr::get($args, 'notifier_version');
		$this->notifier_url     = Arr::get($args, 'notifier_url');

		$this->ignore            = Arr::get($args, 'ignore', array());
		$this->ignore_by_filters = Arr::get($args, 'ignore_by_filters', array());
		$this->backtrace_filters = Arr::get($args, 'backtrace_filters', array());
		$this->params_filters    = Arr::get($args, 'params_filters', array());

		if (isset($args['parameters']))
		{
			$this->params = $args['parameters'];
		}
		elseif (isset($args['params']))
		{
			$this->params = $args['params'];
		}

		if (isset($args['component']))
		{
			$this->component = $args['component'];
		}
		elseif (isset($args['controller']))
		{
			$this->component = $args['controller'];
		}
		elseif (isset($this->params['controller']))
		{
			$this->component = $this->params['controller'];
		}

		if (isset($args['action']))
		{
			$this->action = $args['action'];
		}
		elseif (isset($this->params['action']))
		{
			$this->action = $this->params['action'];
		}


		$this->exception = Arr::get($args, 'exception');

		if ($this->exception instanceof \Exception)
		{
			$backtrace = $this->exception->getTrace();

			if (empty($backtrace))
			{
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			}

			$this->error_class   = get_class($this->exception);
			$this->error_message = HoneybadgerError::text($this->exception);
		}
		else
		{
			if (isset($args['backtrace']) AND is_array($args['backtrace']))
			{
				$backtrace = $args['backtrace'];
			}
			else
			{
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			}

			$this->error_class   = Arr::get($args, 'error_class');
			$this->error_message = Arr::get($args, 'error_message', 'Notification');
		}

		$this->backtrace = Backtrace::parse($backtrace, array(
			'filters' => $this->backtrace_filters,
		));

		$this->hostname = gethostname();

		$this->source_extract_radius = Arr::get($args, 'source_extract_radius', 2);
		$this->source_extract        = $this->extract_source_from_backtrace();

		$this->send_request_session = Arr::get($args, 'send_request_session', TRUE);

		$this->find_session_data();
		$this->clean_params();
		$this->set_context();
	}

	public function ignored()
	{
		if (Filter::ignore_by_class($this->ignore, $this->exception))
			return TRUE;

		foreach ($this->ignore_by_filters as $filter)
		{
			if (call_user_func($filter, $this))
				return TRUE;
		}

		return FALSE;
	}

	public function deliver()
	{
		return Honeybadger::$sender->send_to_honeybadger($this);
	}

	public function as_array()
	{
		$cgi_data = $this->cgi_data->as_array();

		return array(
			'notifier' => array(
				'name'     => $this->notifier_name,
				'url'      => $this->notifier_url,
				'version'  => $this->notifier_version,
				'language' => 'php',
			),
			'error' => array(
				'class'     => $this->error_class,
				'message'   => $this->error_message,
				'backtrace' => $this->backtrace->as_array(),
				'source'    => $this->source_extract ?: NULL,
			),
			'request' => array(
				'url'       => $this->url,
				'component' => $this->component,
				'action'    => $this->action,
				'params'    => empty($this->params) ? NULL : $this->params,
				'session'   => empty($this->params) ? NULL : $this->session_data,
				'cgi_data'  => empty($cgi_data) ? NULL : $cgi_data,
				'context'   => $this->context,
			),
			'server' => array(
				'project_root'     => $this->project_root,
				'environment_name' => $this->environment_name,
				'hostname'         => $this->hostname,
			),
		);
	}

	private function extract_source_from_backtrace()
	{
		if ( ! $this->backtrace->has_lines())
			return NULL;

		if ($this->backtrace->has_application_lines())
		{
			$line = $this->backtrace->application_lines[0];
		}
		else
		{
			$line = $this->backtrace->lines[0];
		}

		return $line->source($this->source_extract_radius);
	}

	private function find_session_data()
	{
		if ( ! $this->send_request_session)
			return;

		if (isset($this->args['session_data']))
		{
			$this->session_data = $this->args['session_data'];
		}
		elseif (isset($this->args['session']))
		{
			$this->session_data = $this->args['session'];
		}
		elseif (isset($_SESSION))
		{
			$this->session_data = $_SESSION;
		}
	}

	private function filter(&$params)
	{
		if (empty($this->params_filters))
			return;

		$params = Filter::params($this->params_filters, $params);
	}

	private function clean_params()
	{
		$this->filter($this->params);

		if ($this->cgi_data)
		{
			$this->filter($this->cgi_data);
		}

		if ($this->session_data)
		{
			$this->filter($this->session_data);
		}
	}

	private function set_context()
	{
		$this->context = Honeybadger::context();

		if (isset($this->args['context']) AND is_array($this->args['context']))
		{
			$this->context = Arr::merge($this->context, $this->args['context']);
		}

		if (empty($this->context))
		{
			$this->context = NULL;
		}
	}

} // End Notice