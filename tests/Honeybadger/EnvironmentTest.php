<?php

namespace Honeybadger;

use \Honeybadger\Util\Arr;

/**
 * Tests Honeybadger\Environment.
 *
 * @group honeybadger
 */
class EnvironmentTest extends TestCase {

	protected $_environment_default = array(
		'_COOKIE' => array(
			'PHPSESSID' => '5jo4beb11n218lr1p0ekdpc916',
			'__utma'    => '1234567890.1234567890.1234567890.1234567890.1234567890.12',
		),
	);

	public function test_factory_should_return_instance_of_environment()
	{
		$this->assertTrue(Environment::factory() instanceof Environment);
	}

	public function test_should_use_server_cookie_superglobals_when_not_supplied_data()
	{
		$environment1 = Environment::factory();
		$environment2 = Environment::factory(Arr::merge($_SERVER, array(
			'rack.request.cookie_hash' => $_COOKIE,
		)));

		$this->assertEquals($environment1, $environment2);
	}

	public function test_protocol_should_be_http_when_https_blank()
	{
		$this->assertEquals('http', Environment::factory(array())->protocol());
	}

	public function test_protocol_should_be_http_when_https_off()
	{
		$this->assertEquals('http', Environment::factory(array(
			'HTTPS' => 'off',
		))->protocol());
	}

	public function test_protocol_should_be_https_when_https_on()
	{
		$this->assertEquals('https', Environment::factory(array(
			'HTTPS' => 'on',
		))->protocol());
	}

	public function provider_https_on()
	{
		return array(
			array(
				'always',
			),
			array(
				'sometimes',
			),
			array(
				'never',
			),
			array(
				'whenever',
			),
			array(
				'maybe',
			),
			array(
				'mostly',
			),
		);
	}

	/**
	 * @dataProvider provider_https_on
	 */
	public function test_protocol_should_be_https_when_https_not_blank($value)
	{
		$this->assertEquals('https', Environment::factory(array(
			'HTTPS' => $value,
		))->protocol());
	}

	public function test_is_secure()
	{
		$this->assertTrue(Environment::factory(array(
			'HTTPS' => 'on',
		))->is_secure());

		$this->assertFalse(Environment::factory(array(
			'HTTPS' => 'off',
		))->is_secure());
	}

	public function test_host_uses_server_name_when_http_host_unavailable()
	{
		$this->assertEquals('example.com', Environment::factory(array(
			'SERVER_NAME' => 'example.com',
		))->host());
	}

	public function test_host_prefers_http_host()
	{
		$this->assertEquals('foo.net', Environment::factory(array(
			'SERVER_NAME' => 'example.com',
			'HTTP_HOST'   => 'foo.net',
		))->host());
	}

	public function test_port_should_return_server_port()
	{
		$this->assertEquals('123', Environment::factory(array(
			'SERVER_PORT' => '123',
		))->port());
	}

	public function test_port_should_detect_default_when_missing()
	{
		$this->assertEquals(80, Environment::factory(array(
			'HTTPS'       => 'off',
		))->port());

		$this->assertEquals(443, Environment::factory(array(
			'HTTPS' => 'on',
		))->port());
	}

	public function test_non_standard_port_when_ssl()
	{
		$this->assertTrue(Environment::factory(array(
			'HTTPS'       => 'on',
			'SERVER_PORT' => 123,
		))->is_non_standard_port());

		$this->assertFalse(Environment::factory(array(
			'HTTPS'       => 'on',
			'SERVER_PORT' => 443,
		))->is_non_standard_port());
	}

	public function test_non_standard_port_when_http()
	{
		$this->assertTrue(Environment::factory(array(
			'HTTPS'       => 'off',
			'SERVER_PORT' => 456,
		))->is_non_standard_port());

		$this->assertFalse(Environment::factory(array(
			'HTTPS'       => 'off',
			'SERVER_PORT' => 80,
		))->is_non_standard_port());
	}

	public function test_url_uses_environment_when_present()
	{
		$env = Environment::factory(array(
			'url' => 'http://example.com/',
		));

		$this->assertEquals('http://example.com/', $env['url']);
	}

	public function test_url_returns_combined_protocol_host_uri_query_string()
	{
		$env = Environment::factory(array(
			'REQUEST_URI'  => '/foo/bar/xyz?one=1&two=2&three=3',
			'SCRIPT_NAME'  => '/foo/index.php',
			'HTTPS'        => 'on',
			'HTTP_HOST'    => 'www.example.com',
			'QUERY_STRING' => 'one=1&two=2&three=3',
		));

		$this->assertEquals('https://www.example.com/foo/bar/xyz?one=1&two=2&three=3', $env['url']);
	}

	public function test_url_adds_port_when_non_standard()
	{
		$env = Environment::factory(array(
			'REQUEST_URI'  => '/foo/bar/xyz?one=1&two=2&three=3',
			'SCRIPT_NAME'  => '/foo/index.php',
			'HTTPS'        => '',
			'HTTP_HOST'    => 'www.example.com',
			'QUERY_STRING' => 'one=1&two=2&three=3',
			'SERVER_PORT'  => '123',
		));

		$this->assertEquals('http://www.example.com:123/foo/bar/xyz?one=1&two=2&three=3', $env['url']);
	}

	public function test_url_returns_null_when_empty_host_and_path()
	{
		$env = Environment::factory(array(
		));

		$this->assertNull($env->url);
	}

}