<?php

namespace Honeybadger;

class FilterTest extends \PHPUnit_Framework_TestCase {

	public function test_should_filter_data_using_supplied_callbacks()
	{
		$callbacks = array();
		$callbacks[] = function($data) {
			$data['bar'] = $data['bar'].'!';
			return $data;
		};
		$callbacks[] = function($data) {
			$data['foo'] = strtoupper($data['foo']);
			return $data;
		};

		$data     = array(
			'foo' => 'meow',
			'bar' => 'ha',
			'baz' => 'wut?',
		);
		$expected = array(
			'filtered_foo' => 'MEOW',
			'filtered_bar' => 'ha!',
			'filtered_baz' => 'wut?',
		);

		$this->assertEquals($expected, Filter::callbacks($callbacks, $data));
	}

	public function test_should_return_null_when_a_callback_returns_null()
	{
		$callbacks = array();
		$callbacks[] = function($data) {
			array_pop($data);
			return $data;
		};
		$callbacks[] = function($data) {
			return;
		};
		$callbacks[] = function($data) {
			return array_map('strtoupper', $data);
		};

		$this->assertNull(Filter::callbacks($callbacks, array(
			'what' => 'is', 'this' => 'i dont even',
		)));
	}

	public function test_should_filter_params()
	{
		$filter_keys = array(
			'password', 'password_confirmation', 'card_number',
		);

		$params = array(
			'name' => 'John Wayne',
			'password' => '1234abcdef',
			'password_confirmation' => '1234abcdef',
			'card_number' => '4111-1111-1111-1111',
		);

		$expected = array(
			'name' => 'John Wayne',
			'password' => '[FILTERED]',
			'password_confirmation' => '[FILTERED]',
			'card_number' => '[FILTERED]',
		);

		$this->assertEquals($expected, Filter::params($filter_keys, $params));
	}

	public function test_should_filter_params_recursively()
	{
		$filter_keys = array(
			'password', 'secret',
		);

		$params = array(
			'data' => array(
				array(
					'password' => 'foo'
				),
				array(
					'baz' => array(
						'secret' => 'bar',
					),
				),
			),
		);

		$expected = array(
			'data' => array(
				array(
					'password' => '[FILTERED]'
				),
				array(
					'baz' => array(
						'secret' => '[FILTERED]',
					),
				),
			),
		);

		$this->assertEquals($expected, Filter::params($filter_keys, $params));
	}

	public function provider_project_root()
	{
		return array(
			array(
				'/var/www/application',
				'[PROJECT_ROOT]/models/user.php',
				'/var/www/application/models/user.php',
			),
			array(
				'',
				'/usr/local/share/php/Some/Library.php',
				'/usr/local/share/php/Some/Library.php',
			),
			array(
				'/var/www/application',
				'[PROJECT_ROOT]/models/user.php',
				'/var/www/application/models/user.php',
			),
			array(
				'/srv/http/app',
				'[PROJECT_ROOT]/index.php',
				'/srv/http/app/index.php',
			),
			array(
				'/srv/http/app',
				'[PROJECT_ROOT]',
				'/srv/http/app',
			),
		);
	}

	/**
	 * @dataProvider provider_project_root
	 */
	public function test_project_root_should_shorten_file_paths($project_root, $shortened, $full)
	{
		Honeybadger::$config = new Config(array(
			'project_root' => $project_root,
		));

		$actual = Filter::project_root(array(
			'file' => $full,
		));

		$this->assertEquals($shortened, $actual['file']);

		Honeybadger::$config = new Config;
	}

	public function provider_expand_paths()
	{
		return array(
			array(
				realpath(__DIR__.'/BacktraceTest.php'),
				__DIR__.'/Util/../BacktraceTest.php',
			),
			array(
				realpath(__DIR__.'/../../README.md'),
				__DIR__.'/Util/../../../././README.md',
			),
			array(
				__DIR__.'/teehee/i/do/not/exist',
				__DIR__.'/teehee/i/do/not/exist',
			),
		);
	}

	/**
	 * @dataProvider provider_expand_paths
	 */
	public function test_expand_paths($expanded, $relative)
	{
		$actual = Filter::expand_paths(array(
			'file' => $relative,
		));

		$this->assertEquals($expanded, $actual['file']);
	}

	public function provider_honeybadger_paths()
	{
		return array(
			array(
				TRUE,
				'lib/Honeybadger',
			),
			array(
				FALSE,
				'bananas.php',
			),

			array(
				TRUE,
				'/usr/local/share/php/lib/honeybadger-php/lib/Honeybadger/Config.php',
			),
		);
	}

	/**
	 * @dataProvider provider_honeybadger_paths
	 */
	public function test_honeybadger_paths($filtered, $file)
	{
		$line = array(
			'file' => $file,
		);

		$actual = Filter::honeybadger_paths($line);

		if ($filtered)
		{
			$this->assertNull($actual);
		}
		else
		{
			$this->assertEquals($line, $actual);
		}
	}

}