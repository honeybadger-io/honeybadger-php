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

}