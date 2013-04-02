<?php

namespace Honeybadger;

use \Honeybadger\Honeybadger;
use \Honeybadger\Config;
use \Honeybadger\Util\Arr;

/**
 * [Slim](http://www.slimframework.com) middleware for catching unhandled
 * exceptions in applications. Notifies Honeybadger of exceptions then rethrows
 * them to allow error handling in other middleware.
 *
 * @package   Honeybadger/Integrations
 * @category  Slim
 */
class Slim extends \Slim\Middleware {

	/**
	 * Configures Honeybadger for the supplied Slim app for exception catching.
	 *
	 * @param   Slim    $app      The Slim app.
	 * @param   array   $options  The config options.
	 * @return  Config  The Honeybadger configuration.
	 */
	protected static function init(\Slim\Slim $app, array $options = array())
	{
		if ($logger = $app->getLog())
		{
			// Wrap the application logger.
			Honeybadger::$logger = new \Honeybadger\Logger\Slim($app->getLog());
		}

		// Add missing, detected options.
		$options = Arr::merge(array(
			'environment_name' => $app->getMode(),
			'framework'        => sprintf('Slim: %s', \Slim\Slim::VERSION),
		), $options);

		// Create a new configuration with the merged options.
		Honeybadger::$config = new Config(
			Honeybadger::$config->merge($options)
		);
	}

	/**
	 * @var  array  Stores configuration settings until needed.
	 */
	protected $options = array();

	/**
	 * Constructs middleware for notifying Honeybadger of uncaught application
	 * exceptions and informing users of error identifiers, when a placeholder
	 * is embedded in the developer's error response body.
	 *
	 * Accepts an array of configuration options which are set on [Config]
	 * before processing requests. See the [Config] class for
	 * available settings.
	 *
	 * ## Informing Users
	 *
	 * Users can be shown an error identifier by adding
	 * `<!-- HONEYBADGER ERROR -->` to an error view. Whenever an uncaught
	 * exception is handled and the placeholder is found,
	 * [Slim::call_and_inform_users] will replace it with the
	 * configured `user_information`.
	 *
	 * By default, results in:
	 *
	 *     Honeybadger Error <error identifier>
	 *
	 * @param  array  $options  The Honeybadger config options.
	 */
	public function __construct(array $options = array())
	{
		$this->options = $options;
	}

	/**
	 * Called by Slim when processing requests. Initializes Honeybadger's
	 * configuration, then continues the middleware call chain. If an uncaught
	 * exception reaches this middleware, it will be sent to Honeybadger if it
	 * is not filtered or ignored.
	 *
	 * When finished, a captured exception (if any) is re-thrown to allow Slim's
	 * error handling to take over.
	 *
	 * The middleware will additionally search for `<!-- HONEYBADGER ERROR -->`
	 * in the response body and replace it with the configured
	 * `user_information`, substituting `{{error_id}}` with the error ID.
	 *
	 * @return  void
	 */
	public function call()
	{
		self::init($this->app, $this->options);

		try
		{
			$this->next->call();
		}
		catch (\Exception $e)
		{
			// Report the exception to Honeybadger and store the error ID in
			// the environment.
			$this->inform_users(
				$this->notify_honeybadger($e)
			);

			// Rethrow the exception to allow other middleware to handle it.
			throw $e;
		}
	}

	/**
	 * @return  void
	 */
	private function inform_users($error_id)
	{
		if (empty(Honeybadger::$config->user_information))
			return;

		$response  = $this->app->response();
		$user_info = $this->user_info(Honeybadger::$config->user_information, $error_id);

		// Substitute placeholder comment with user information.
		$response->body(str_replace(
			'<!-- HONEYBADGER ERROR -->', $user_info, $response->body()
		));
	}

	/**
	 * @return  void
	 */
	private function user_info($info, $error_id)
	{
		return preg_replace('/\{\{\s*error_id\s*\}\}/', $error_id, $info);
	}

	/**
	 * @return  boolean
	 */
	private function ignored_user_agent($env)
	{
		return in_array($env['USER_AGENT'], Honeybadger::$config->ignore_user_agents);
	}

	/**
	 * @return  void
	 */
	private function notify_honeybadger(\Exception $exception)
	{
		$env = $this->app->environment();

		if ( ! $this->ignored_user_agent($env))
		{
			return $env['honeybadger.error_id'] = Honeybadger::notify_or_ignore(
				$exception, $this->notice_options($env)
			);
		}
	}

	/**
	 * Builds options including CGI data, request and environment parameters,
	 * and the request URL to be used when building a new notice.
	 *
	 * @param   Slim\Environment  $env  The application environment.
	 * @return  array  The notice options.
	 */
	private function notice_options($env)
	{
		$request = $this->app->request();

		return array(
			'cgi_data' => $this->formatted_cgi_data($env),
			'params'   => $this->combined_params($request),
			'url'      => $this->request_url($env, $request),
		);
	}

	/**
	 * Formats Slim's app environment into an array suitable for sending to
	 * Honeybadger. This includes replacing keys starting with `slim.` with
	 * `rack.` so that Honeybadger can pick up on cookies and other data.
	 *
	 * @param   Slim\Environment  $env  The application environment.
	 * @return  array  The formatted CGI data.
	 */
	private function formatted_cgi_data($env)
	{
		$cgi_data = array();

		foreach ($env as $key => $value)
		{
			// Honeybadger has an easier time picking up details when they
			// follow Rack conventions. Conveniently, Slim follows these
			// conventions as well.
			$key = preg_replace('/^slim\./', 'rack.', $key);

			if (is_object($value) AND ! method_exists($value, '__toString'))
			{
				// Render a generic inspection for objects that cannot be
				// converted to strings.
				$value = '#<'.get_class($value).':'.spl_object_hash($value).'>';
			}

			$cgi_data[$key] = (string) $value;
		}

		return $cgi_data;
	}

	/**
	 * Combines the request and route parameters into one array for
	 * Honeybadger notices.
	 *
	 * @param   Slim\Http\Request  $request
	 * @return  array  The combined request and route parameters.
	 */
	private function combined_params($request)
	{
		$router = $this->app->router();

		// Find the matching route for the request, to extract parameters for
		// routes such as: `/books/:id`.
		$router->getMatchedRoutes($request->getMethod(),
		                          $request->getPathInfo());

		if ($route = $router->getCurrentRoute())
		{
			$params = $route->getParams();
		}
		else
		{
			$params = array();
		}

		// Merge the route and request parameters into one array.
		return Arr::merge($request->params(), $params);
	}

	/**
	 * Reconstructs a full URL for the request from Slim's request object.
	 *
	 * @param   Slim\Environment   $env  The application environment.
	 * @param   Slim\Http\Request  $request
	 * @return  string  The request URL.
	 */
	private function request_url($env, $request)
	{
		$url = implode('', array(
			$request->getUrl(), $request->getRootUri(), $request->getPathInfo(),
		));

		if (isset($env['QUERY_STRING']) AND ! empty($env['QUERY_STRING']))
		{
			$url .= '?'.$env['QUERY_STRING'];
		}

		return $url;
	}

} // End Slim