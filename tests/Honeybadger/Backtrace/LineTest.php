<?php

namespace Honeybadger\Backtrace;

use Honeybadger\Backtrace\Line;

class LineTest extends \PHPUnit_Framework_TestCase {

	public function test_attributes_read_only()
	{
		$attributes = array('file', 'number', 'method', 'source',
			'filtered_file', 'filtered_number', 'filtered_method');
		$line = new Line('foo', 'bar', 'baz');

		foreach ($attributes as $attribute)
		{
			$line->$attribute;

			try
			{
				$line->$attribute = 'foo';
			}
			catch (\Exception $ex)
			{
				continue;
			}

			$this->fail('An exception was not raised for '.$line);
		}
	}

	public function test_parse_filters_with_provided_callbacks()
	{
		$scream = function($line) {
			$line['file'] = strtoupper($line['file']);
			return $line;
		};

		$whisper = function($line) {
			$line['function'] = strtolower($line['function']);
			return $line;
		};

		$lie = function($line) {
			$line['line'] *= 3;
			return $line;
		};

		$callbacks = array($scream, $whisper, $lie);

		$data = array(
			'file'     => 'whatisthis.php',
			'line'     => 4,
			'function' => 'I_DONT_EVEN',
		);

		$line = Line::parse($data, array('filters' => $callbacks));
		$expected = new Line('WHATISTHIS.PHP', 12, 'i_dont_even');

		$this->assertEquals((string) $line, (string) $expected);
	}

	public function test_parse_returns_null_when_callback_returns_null()
	{
		$callback = function($line){};
		$line = Line::parse(array(), array(
			'filters' => array($callback),
		));

		$this->assertNull($line);
	}

	public function provider_parse_returns_line()
	{
		return array(
			array(
				new Line('path/to/awesome.php', 14, 'failz'),
				array(
					'file'     => 'path/to/awesome.php',
					'line'     => 14,
					'function' => 'failz',
				),
			),
			array(
				new Line('feeling', 'sorta', 'lucky'),
				array(
					'file'     => 'feeling',
					'line'     => 'sorta',
					'function' => 'lucky',
				),
			),
		);
	}

	/**
	 * @dataProvider provider_parse_returns_line
	 */
	public function test_parse_returns_line($expected, $data)
	{
		$line = Line::parse($data);
		$this->assertTrue($line->equals($expected));
	}

	public function provider_string_conversion()
	{
		return array(
			array(
				"[PROJECT_ROOT]/app/models/user.php:14:in `find'",
				new Line('[PROJECT_ROOT]/app/models/user.php', 14, 'find'),
			),
			array(
				"{PHP internal call}:1:in `baz'",
				new Line('{PHP internal call}', 1, 'baz'),
			),
			array(
				"filtered:28:in `redacted'",
				new Line('once', 'upon', 'a time', 'filtered', 28, 'redacted'),
			),
		);
	}

	/**
	 * @dataProvider provider_string_conversion
	 */
	public function test_string_conversion_returns_ruby_style_backtrace_line($expected, $line)
	{
		$this->assertEquals($expected, (string) $line);
	}

	public function provider_is_application()
	{
		return array(
			array(
				TRUE,
				new Line('[PROJECT_ROOT]/foo.php', 11, 'bar'),
			),
			array(
				FALSE,
				new Line(' [PROJECT_ROOT]/bar.php', 22, 'baz'),
			),
			array(
				TRUE,
				new Line('[PROJECT_ROOT]', 123, 'something'),
			),
			array(
				FALSE,
				new Line('/var/www/baz.php', 58, 'echo'),
			),
		);
	}

	/**
	 * @dataProvider provider_is_application
	 */
	public function test_is_application($expected, $line)
	{
		$this->assertEquals($expected, $line->is_application());
	}

	public function test_source()
	{
		$line = new Line(__FILE__, 6, 'foo');
		$this->assertEquals(array(
			"\n",
			"use Honeybadger\Backtrace\Line;\n",
			"\n",
			"class LineTest extends \PHPUnit_Framework_TestCase {\n",
			"\n",
		), $line->source);
	}

	public function test_source_returns_empty_array_for_non_existent()
	{
		$line = new Line(NULL, 123, 'something');
		$this->assertEmpty($line->source);
	}

	public function provider_to_array()
	{
		return array(
			array(
				array(
					'file'   => 'foo',
					'number' => 'bar',
					'method' => 'baz'
				),
				new Line('foo', 'bar', 'baz'),
			),
			array(
				array(
					'file'   => 'this',
					'number' => 'is',
					'method' => 'filtered'
				),
				new Line('foo', 'bar', 'baz', 'this', 'is', 'filtered'),
			),
			array(
				array(
					'file'   => 'this is',
					'number' => 'partially',
					'method' => 'filtered'
				),
				new Line('this is', 'partially', 'baz', NULL, NULL, 'filtered'),
			),
		);
	}

	/**
	 * @dataProvider provider_to_array
	 */
	public function test_to_array($expectation, $line)
	{
		$this->assertEquals($expectation, $line->to_array());
	}

}