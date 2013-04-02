<?php

namespace Honeybadger;

use \Honeybadger\Util\Arr;

class Filter {

	/**
	 * Applies a list of callbacks to an array of data.
	 *
	 * @param   array    $callbacks  Callbacks to run.
	 * @param   array    $data       Data to filter.
	 * @return  array    Filtered data
	 */
	public static function callbacks(array $callbacks, array $data)
	{
		if (empty($callbacks) OR empty($data))
			return $data;

		$filtered = array();

		foreach ($callbacks as $callback)
		{
			$data = call_user_func($callback, $data);

			// A filter wants to hide this data.
			if ($data === NULL)
				return;
		}

		foreach ($data as $key => $value)
		{
			$filtered['filtered_'.$key] = $value;
		}

		return $filtered;
	}

	/**
	 * Filters a supplied `array` of `$params`, searching for `$keys` and
	 * replacing each occurance with `[FILTERED]`.
	 *
	 * @param   array  $keys
	 * @param   array  $params Parameters to filter.
	 * @return  array  Filtered parameters.
	 */
	public static function params(array $keys = array(), $params = array())
	{
		if (empty($keys) OR empty($params))
			return $params;

		foreach ($params as $param => &$value)
		{
			if (is_array($value))
			{
				$value = self::params($keys, $value);
			}
			elseif ( ! is_integer($param) AND in_array($param, $keys))
			{
				$value = '[FILTERED]';
			}
		}

		return $params;
	}

	public static function ignore_by_class(array $classes = array(), $object)
	{
		if ( ! is_object($object))
			return FALSE;

		$object_class = get_class($object);

		foreach ($classes as $class)
		{
			// Remove trailing and prefixing backslash (unnecessary namespaces)
			$class = trim($class, '\\');

			if ($object_class === $class)
				return TRUE;

			if (is_subclass_of($object, $class))
				return TRUE;
		}

		return FALSE;
	}

	/**
	 * Replaces occurances of configured project root with `[PROJECT_ROOT]` to
	 * simplify backtraces.
	 *
	 * @example
	 *     Filter::project_root(array(
	 *         'file' => 'path/to/my/app/models/user.php',
	 *     ));
	 *     // => array('file' => '[PROJECT_ROOT]/models/user.php')
	 *
	 * @param   array  Unparsed backtrace line.
	 * @return  array  Filtered backtrace line.
	 */
	public static function project_root($line)
	{
		$config = Notice::$current ?: Honeybadger::$config;
		$project_root = (string) $config->project_root;

		if (strlen($project_root) === 0)
			return $line;

		$pattern = '/'.preg_quote($project_root, '/').'/';
		$line['file'] = preg_replace($pattern, '[PROJECT_ROOT]', $line['file']);

		return $line;
	}

	/**
	 * Attempts to expand paths to their real locations, if possible.
	 *
	 * @example
	 *     Filter::expand_paths(array(
	 *         'file' => '/etc/./../usr/local',
	 *     ));
	 *     // => array('file' => '/usr/local')
	 */
	public static function expand_paths($line)
	{
		if ($path = realpath($line['file']))
			$line['file'] = $path;

		return $line;
	}

	/**
	 * Removes Honeybadger from backtraces.
	 *
	 * @example
	 *     Filter::honeybadger_paths(array(
	 *         'file' => 'path/to/lib/Honeybadger/Honeybadger.php',
	 *     ));
	 *     // => NULL
	 */
	public static function honeybadger_paths($line)
	{
		if ( ! preg_match('/lib\/Honeybadger/', $line['file']))
			return $line;
	}

}