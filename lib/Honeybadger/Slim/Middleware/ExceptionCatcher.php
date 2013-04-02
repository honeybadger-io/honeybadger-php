<?php

namespace Honeybadger\Slim\Middleware;

use \Honeybadger\Honeybadger;
use \Honeybadger\Util\Arr;

/**
 * TODO: Cleanup and unit test.
 */
class ExceptionCatcher extends \Slim\Middleware {

	public function call()
	{
		$env = $this->app->environment();

		try
		{
			$this->next->call();
		}
		catch (\Exception $e)
		{
			// Report the exception to Honeybadger and store the error ID in
			// the environment.
			$env['honeybadger.error_id'] = $this->notify_honeybadger($e);

			// Rethrow the exception to allow other middleware to handle it.
			throw $e;
		}
	}

	private function notify_honeybadger($exception)
	{
		if ( ! $this->ignored_user_agent())
			return Honeybadger::notify_or_ignore($exception, $this->notice_options());
	}

	private function ignored_user_agent()
	{
		$env = $this->app->environment();
		return in_array($env['USER_AGENT'],
			Honeybadger::$config->ignore_user_agents);
	}

	private function notice_options()
	{
		$request = $this->app->request();
		$route = $this->app->router();

		return array(
			'environment_name' => $this->app->getMode(),
			'cgi_data'         => $this->formatted_cgi_data(),
			'params'           => $this->params(),
			'url'              => $this->url(),
		);
	}

	private function formatted_cgi_data()
	{
		$env      = $this->app->environment();
		$cgi_data = array();

		foreach ($env as $key => $value)
		{
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

	private function params()
	{
		$request = $this->app->request();
		$router  = $this->app->router();

		$router->getMatchedRoutes($request->getMethod(), $request->getPathInfo());
		if ($route = $router->getCurrentRoute())
		{
			$params = $route->getParams();
		}

		return Arr::merge($request->params(), $params);
	}

	private function url()
	{
		$request = $this->app->request();
		$env     = $this->app->environment();
		$url     = $request->getUrl().$request->getRootUri().$request->getPathInfo();

		if (isset($env['QUERY_STRING']) AND ! empty($env['QUERY_STRING']))
		{
			$url .= '?'.$env['QUERY_STRING'];
		}

		return $url;
	}

} // End ExceptionCatcher