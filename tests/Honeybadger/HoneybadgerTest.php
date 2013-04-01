<?php

namespace Honeybadger;

/**
 * Tests Honeybadger.
 *
 * @group honeybadger
 */
class HoneybadgerTest extends TestCase {

	protected $context = array(
		'user' => array(
			'id'   => 123,
			'name' => 'Gabriel Evans',
		),
	);

	public function setUp()
	{
		parent::setUp();
		Honeybadger::reset_context($this->context);
	}

	public function tearDown()
	{
		parent::tearDown();
		Honeybadger::reset_context();
	}

	public function test_initialized_with_void_logger()
	{
		$this->assertTrue(Honeybadger::$logger instanceof Logger\Void);
	}

	public function test_initialized_with_config()
	{
		$this->assertTrue(Honeybadger::$config instanceof Config);
	}

	public function test_initialized_with_sender()
	{
		$this->assertTrue(Honeybadger::$sender instanceof Sender);
	}

	public function test_context_merges_supplied_data()
	{
		Honeybadger::context(array(
			'user' => array(
				'id'   => 123,
				'name' => 'Gabriel Evans',
			),
			'device' => 'iPhone',
		));

		$this->assertEquals(array(
			'user' => array(
				'id'   => 123,
				'name' => 'Gabriel Evans',
			),
			'device' => 'iPhone',
		), Honeybadger::context());
	}

	public function test_context_returns_data()
	{
		$this->assertEquals($this->context, Honeybadger::context());
	}

	public function test_reset_context_should_empty_context()
	{
		Honeybadger::reset_context();
		$this->assertEmpty(Honeybadger::context());
	}

	public function test_reset_context_should_return_empty_array()
	{
		$this->assertEmpty(Honeybadger::reset_context());
	}

}