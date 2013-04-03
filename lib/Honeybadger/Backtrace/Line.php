<?php

namespace Honeybadger\Backtrace;

use \Honeybadger\Filter;
use \Honeybadger\Util\SemiOpenStruct;

/**
 * Handles backtrace parsing line by line.
 *
 * @package   Honeybadger
 * @category  Backtrace
 */
class Line extends SemiOpenStruct {

	protected $_attribute_methods = array('source');

	/**
	 * @var  string  The file portion of the line.
	 */
	protected $file;

	/**
	 * @var  integer  The line number portion of the line.
	 */
	protected $number;

	/**
	 * @var  string  The method of the line (such as index).
	 */
	protected $method;

	/**
	 * @var  string  The source code surrounding the matching line.
	 */
	protected $source;

	/**
	 * @var  string  Filtered representation of [Line::$file].
	 */
	protected $filtered_file;

	/**
	 * @var  string  Filtered representation of [Line::$number].
	 */
	protected $filtered_number;

	/**
	 * @var  string  Filtered representation of [Line::$method].
	 */
	protected $filtered_method;

	/**
	 * Parses a single line of a given backtrace.
	 *
	 * @param   array   $unparsed_line   The raw line from `caller` or some backtrace.
	 * @param   array   $options
	 * @return  Line    The parsed backtrace line.
	 */
	public static function parse(array $unparsed_line, array $options = array())
	{
		if ( ! isset($unparsed_line['file']) OR empty($unparsed_line['file']))
		{
			$unparsed_line['file'] = '{PHP internal call}';
		}

		if ( ! isset($options['filters']))
		{
			$options['filters'] = array();
		}

		$filtered = Filter::callbacks($options['filters'], $unparsed_line);

		if ($filtered === NULL)
			return;

		// Extract the filtered line parameters
		extract($filtered + array(
			'filtered_file'     => NULL,
			'filtered_line'     => NULL,
			'filtered_function' => NULL,
		));

		// Extract the original line parameters
		extract($unparsed_line + array(
			'file'     => '',
			'line'     => '',
			'function' => '',
		));

		return new self($file, $line, $function, $filtered_file,
			$filtered_line, $filtered_function);
	}

	/**
	 * Instantiates a new backtrace line from a given filename, line number, and
	 * method name.
	 *
	 * @param  string   $file    The filename in the given backtrace line
	 * @param  integer  $number  The line number of the file
	 * @param  string   $method  The method referenced in the given backtrace line
	 */
	public function __construct($file, $number, $method, $filtered_file = NULL,
                                $filtered_number = NULL, $filtered_method = NULL)
	{
		if ($filtered_file === NULL)
		{
			$filtered_file = $file;
		}

		if ($filtered_number === NULL)
		{
			$filtered_number = $number;
		}

		if ($filtered_method === NULL)
		{
			$filtered_method = $method;
		}

		$this->filtered_file   = $filtered_file;
		$this->filtered_number = $filtered_number;
		$this->filtered_method = $filtered_method;
		$this->file            = $file;
		$this->number          = $number;
		$this->method          = $method;
	}

	/**
	 * Formats the backtrace line as a string.
	 *
	 *     echo $line; // => "app/models/user.php:109:in `some_method'"
	 *
	 * @return  string  The backtrace line.
	 */
	public function __toString()
	{
		return sprintf("%s:%d:in `%s'", $this->filtered_file,
			$this->filtered_number, $this->filtered_method);
	}

	/**
	 * Checks if this `Line` matches another supplied `Line`.
	 *
	 * @param   Line     $other  The `Line` to match.
	 * @return  boolean  `TRUE` if objects match, `FALSE` otherwise.
	 */
	public function equals($other)
	{
		return ( (string) $this == (string) $other);
	}

	/**
	 * Checks if this `Line` is a part of the configured project. Ignores files
	 * under `[PROJECT_ROOT]/vendor`.
	 *
	 * @return  boolean  `TRUE` if application line, `FALSE` otherwise.
	 */
	public function is_application()
	{
		return (strpos($this->filtered_file, '[PROJECT_ROOT]') === 0 AND
		        strpos($this->filtered_file, '[PROJECT_ROOT]/vendor') === FALSE);
	}

	/**
	 * Extracts the source code of the line. Optionally, `$radius` can be
	 * supplied to specificy the number of lines before and after to extract.
	 *
	 * @param   integer  $radius  Radius to extract.
	 * @return  array    The extracted source code.
	 */
	public function source($radius = 2)
	{
		if ($this->source)
			return $this->source;

		return $this->source = $this->get_source($this->file, $this->number, $radius);
	}

	/**
	 * Formats the backtrace line as an array.
	 *
	 * @return  array  The backtrace line
	 */
	public function as_array()
	{
		return array(
			'file'   => $this->filtered_file,
			'number' => $this->filtered_number,
			'method' => $this->filtered_method,
		);
	}

	/**
	 * Extracts source code from the specified `$file`, starting `$radius`
	 * lines before the specified `$number`.
	 *
	 * @return  array  The extracted source.
	 */
	private function get_source($file, $number, $radius = 2)
	{
		if ( ! is_file($file) OR ! is_readable($file))
		{
			return array();
		}

		$before = $after = $radius;
		$start  = ($number - 1) - $before;

		if ($start <= 0)
		{
			$start  = 1;
			$before = 1;
		}

		$duration = $before + 1 + $after;
		$size     = $start + $duration;
		$lines    = array();

		$f = fopen($file, 'r');

		for ($l = 1; $l < $size; $l++)
		{
			$line = fgets($f);

			if ($l < $start)
				continue;

			$lines["$l"] = $this->trim_line($line);
		}

		return $lines;
	}

	/**
	 * Replaces tabs with spaces and strips endlines and trailing whitespace
	 * from the supplied `$line`.
	 *
	 * @param   string  $line  The line to trim.
	 * @return  string  The trimmed line.
	 */
	private function trim_line($line)
	{
		$trimmed = trim($line, "\n\r\0\x0B");

		return preg_replace(array(
			'/\s*$/D',
			'/\t/'
		), array(
			'',
			'    ',
		), $trimmed);
	}

} // End Line