<?php

namespace Honeybadger;

use Honeybadger\Backtrace\Line;

/**
 * Tests Honeybadger\Backtrace.
 *
 * @group honeybadger
 */
class BacktraceTest extends \PHPUnit_Framework_TestCase {

	public function test_parse_drops_null_lines()
	{
		$callback = function($line) {
			if ($line['file'] == 'dropme.php')
				return NULL;
			return $line;
		};

		$lines = array(
			array(
				'file'     => 'dropme.php',
				'line'     => 1,
				'function' => '',
			),
			array(
				'file'     => '[PROJECT_ROOT]/app.php',
				'line'     => 789,
				'function' => 'get',
			),
		);

		$backtrace = Backtrace::parse($lines, array(
			'filters' => array($callback),
		));

		$this->assertEquals(array(Line::parse($lines[1])), $backtrace->lines);
	}

	public function test_parse_returns_backtrace_containing_parsed_lines()
	{
		$unparsed_lines = array(
			array(
				'file'     => 'hello.php',
				'line'     => 1,
				'function' => 'world',
			),
			array(
				'file'     => 'world.php',
				'line'     => 2,
				'function' => 'hello',
			),
		);

		$backtrace = Backtrace::parse($unparsed_lines);

		$expected_lines = array(
			new Line('hello.php', 1, 'world'),
			new Line('world.php', 2, 'hello'),
		);

		$this->assertEquals($expected_lines, $backtrace->lines);
	}

	public function test_attributes_read_only()
	{
		$attributes = array('lines', 'application_lines');
		$line = new Backtrace;

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

	public function test_lines()
	{
		$lines = array(
			new Line('[PROJECT_ROOT]/models/user.php', 3, 'find'),
			new Line('[PROJECT_ROOT]/app.php', 789, 'get'),
			new Line('[PROJECT_ROOT]/vendor/some_lib.php', 456, 'run'),
			new Line('/usr/local/share/some_other_lib.php', 123, 'execute'),
		);

		$backtrace = new Backtrace($lines);

		$this->assertEquals($lines, $backtrace->lines);
	}

	public function test_application_lines_exclude_non_project_lines()
	{
		$lines = array(
			new Line('[PROJECT_ROOT]/models/user.php', 3, 'find'),
			new Line('[PROJECT_ROOT]/app.php', 789, 'get'),
			new Line('[PROJECT_ROOT]/vendor/some_lib.php', 456, 'run'),
			new Line('/usr/local/share/some_other_lib.php', 123, 'execute'),
		);

		$backtrace = new Backtrace($lines);

		$this->assertEquals(array($lines[0], $lines[1]), $backtrace->application_lines);
	}

	public function test_has_lines()
	{
		$backtrace = new Backtrace;
		$this->assertFalse($backtrace->has_lines());

		$backtrace = new Backtrace(array(
			new Line('super_cool_file.php', 3, 'super_cool_method')
		));
		$this->assertTrue($backtrace->has_lines());
	}

	public function test_has_application_lines()
	{
		$backtrace = new Backtrace;
		$this->assertFalse($backtrace->has_application_lines());

		$backtrace = new Backtrace(array(
			new Line('[PROJECT_ROOT]/super_cool_file.php', 3, 'super_cool_method')
		));
		$this->assertTrue($backtrace->has_application_lines());
	}

	public function test_string_conversion_returns_ruby_style_backtrace()
	{
		$lines = array(
			new Line('yet_another_lame_example.php', 1337, 'leet'),
			new Line('yet_another_lame_example.php', 7331, 'teel'),
		);

		$backtrace = new Backtrace($lines);

		$expected  = "yet_another_lame_example.php:1337:in `leet'\n";
		$expected .= "yet_another_lame_example.php:7331:in `teel'";

		$this->assertEquals($expected, (string) $backtrace);
	}

	public function test_to_array_returns_each_line_as_array()
	{
		$lines = array(
			new Line('some_file.php', 21, 'baz'),
			new Line('another_file.php', 33, 'bar'),
		);

		$backtrace = new Backtrace($lines);

		$expected = array(
			array(
				'file'   => 'some_file.php',
				'number' => 21,
				'method' => 'baz',
			),
			array(
				'file'   => 'another_file.php',
				'number' => 33,
				'method' => 'bar',
			),
		);

		$this->assertEquals($expected, $backtrace->to_array());
	}

	public function test_to_json()
	{
		$lines = array(
			new Line('foo', 11, 'bar'),
			new Line('baz', 2, 'something'),
		);

		$backtrace = new Backtrace($lines);

		$expected = json_encode(array(
			array(
				'file'   => 'foo',
				'number' => 11,
				'method' => 'bar',
			),
			array(
				'file'   => 'baz',
				'number' => 2,
				'method' => 'something',
			),
		));

		$this->assertEquals($expected, $backtrace->to_json());
	}

}