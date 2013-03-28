<?php

namespace Honeybadger;

class Filter {

	/**
	 * Applies a list of callbacks to an array of data.
	 *
	 * @param   array    $callbacks  Callbacks to run.
	 * @param   array    $data       Data to filter.
	 * @return  array    Filtered data
	 */
	public static function run_callbacks(array $callbacks = array(), $data)
	{
		if (empty($callbacks) OR empty($data))
		{
			return $data;
		}

		$filtered = array();

		foreach ($callbacks as $callback)
		{
			$data = $callback($data);

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

	public static function params(array $keys = array(), array $params = array())
	{
		foreach ($keys as $key)
		{
			if ( ! array_key_exists($key, $params))
				continue;

			$params[$key] = '[FILTERED]';
		}

		return $params;
	}

}