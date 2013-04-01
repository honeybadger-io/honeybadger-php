<?php

namespace Honeybadger;

/**
 * Tests Honeybadger\Config.
 *
 * @group honeybadger
 */
class ConfigTest extends \PHPUnit_Framework_TestCase {

	private static $options = array(
		'api_key', 'host', 'port', 'secure', 'http_open_timeout',
		'http_read_timeout', 'proxy_host', 'proxy_port', 'proxy_user',
		'proxy_pass', 'backtrace_filters', 'params_filters',
		'ignore_by_filters', 'ignore', 'ignore_user_agnets',
		'development_environments', 'environment_name', 'project_root',
		'notifier_name', 'notifier_version', 'notifier_url', 'logger',
		'user_information', 'framework', 'source_extract_radius',
		'send_request_session', 'debug', 'certificate_authority',
	);

	public function test_config_has_default_params_filters()
	{
		$this->assertNotEmpty(Config::$default_params_filters);
	}

	public function test_config_has_default_backtrace_filters()
	{
		$this->assertNotEmpty(Config::$default_backtrace_filters);
	}

	public function test_new_config_detects_certificate_authority_when_null()
	{
		$config = new Config;
		$this->assertEquals(realpath(__DIR__.'/../../resources/ca-bundle.crt'),
			$config->certificate_authority);
	}

	public function test_new_config_does_not_overwrite_certificate_authority()
	{
		$config = new Config(array(
			'certificate_authority' => 'foo',
		));

		$this->assertEquals('foo', $config->certificate_authority);
	}

	public function test_new_config_sets_default_notifier_info()
	{
		$config = new Config;

		$this->assertEquals(Honeybadger::NOTIFIER_NAME, $config->notifier_name);
		$this->assertEquals(Honeybadger::VERSION, $config->notifier_version);
		$this->assertEquals(Honeybadger::NOTIFIER_URL, $config->notifier_url);
	}

	public function test_new_config_sets_supplied_options()
	{
		$options = array(
			'api_key'                  => '123abc',
			'host'                     => 'my-notifier.io',
			'port'                     => 123,
			'secure'                   => FALSE,
			'certificate_authority'    => '/etc/ssl/ca-bundle.crt',
			'http_open_timeout'        => 11,
			'http_read_timeout'        => 21,
			'proxy_host'               => '127.0.0.1',
			'proxy_port'               => '8118',
			'proxy_user'               => 'admin',
			'proxy_pass'               => '12345',
			'development_environments' => array('bananas'),
			'environment_name'         => 'winning',
			'framework'                => 'DIY',
			'source_extract_radius'    => 10,
			'send_request_session'     => FALSE,
			'debug'                    => TRUE,
		);

		$config = new Config($options);

		foreach ($options as $key => $value)
		{
			$this->assertEquals($value, $config->$key);
		}
	}

	public function test_new_config_merges_default_params_filters()
	{
		$config = new Config(array(
			'params_filters' => array('ssn'),
		));

		$expected = array(
			'ssn',
			'password',
			'password_confirmation',
			'HTTP_AUTHORIZATION',
			'HTTP_PROXY_AUTHORIZATION',
			'PHP_AUTH_DIGEST',
			'PHP_AUTH_PW',
		);

		$this->assertEquals($expected, $config->params_filters);
	}

	public function test_new_config_merges_default_backtrace_filters()
	{
		$original_default_backtrace_filters = Config::$default_backtrace_filters;
		Config::$default_backtrace_filters = array('strtolower');

		$config = new Config(array(
			'backtrace_filters' => array('strtoupper'),
		));

		$this->assertEquals(array(
			'strtoupper', 'strtolower',
		),$config->backtrace_filters);

		Config::$default_backtrace_filters = $original_default_backtrace_filters;
	}

	public function test_new_config_merges_default_ignored_classes()
	{
		$original_default_ignore = Config::$default_ignore;
		Config::$default_ignore = array('Exception');

		$config = new Config(array(
			'ignore' => array('HoneybadgerError'),
		));

		$this->assertEquals(array(
			'HoneybadgerError', 'Exception',
		),$config->ignore);

		Config::$default_ignore = $original_default_ignore;
	}

	public function test_filter_backtrace_adds_supplied_callback_to_backtrace_filters()
	{
		$config = new Config;
		$config->filter_backtrace('foo');

		$this->assertEquals('foo', end($config->backtrace_filters));
	}

	public function test_ignore_by_filter_adds_supplied_callback_to_ignore_by_filters()
	{
		$config = new Config;
		$config->ignore_by_filter('bar');

		$this->assertEquals('bar', end($config->ignore_by_filters));
	}

	public function test_ignore_only_overrides_existing_ignores()
	{
		$config = new Config;
		$config->ignore_only('Some');
		$config->ignore_only('Error');
		$config->ignore_only('GenericError', 'Exception');

		$this->assertEquals(array('GenericError', 'Exception'), $config->ignore);
	}

	public function test_ignore_user_agents_only_overrides_existing_ignored_user_agents()
	{
		$config = new Config;
		$config->ignore_user_agents_only('Firefox');
		$config->ignore_user_agents_only('Chrome');
		$config->ignore_user_agents_only('Mozilla', 'Internet Explorer');

		$this->assertEquals(array('Mozilla', 'Internet Explorer'), $config->ignore_user_agents);
	}

	public function test_merge_returns_array_merged_with_supplied_options()
	{
		$config = new Config(array(
			'api_key'           => 'foo',
			'http_open_timeout' => 10,
		));

		$actual = $config->merge(array(
			'api_key' => 'bar',
			'host'    => 'localhost',
		));

		$this->assertEquals('bar', $actual['api_key']);
		$this->assertEquals('localhost', $actual['host']);
		$this->assertEquals(10, $actual['http_open_timeout']);
	}

	public function test_should_be_public_when_environment_not_development()
	{
		$config = new Config(array(
			'environment_name' => 'production',
		));

		$this->assertTrue($config->is_public());
	}

	public function test_should_not_be_public_when_environment_development()
	{
		$config = new Config(array(
			'environment_name' => 'development',
		));

		$this->assertFalse($config->is_public());
	}

	public function test_options_should_be_accessible()
	{
		$config = new Config;

		foreach (self::$options as $option)
		{
			$config->$option = 'foo';
			$this->assertEquals('foo', $config->$option);
		}
	}

	public function test_accessible_as_array()
	{
		$config = new Config;

		foreach (self::$options as $option)
		{
			$config[$option] = 'bar';
			$this->assertEquals('bar', $config[$option]);
			$this->assertEquals('bar', $config->$option);
		}
	}

	public function test_changing_secure_updates_port_with_default()
	{
		$config = new Config(array('secure' => FALSE));
		$this->assertEquals(80, $config->port);

		$config->secure = TRUE;
		$this->assertEquals(443, $config->port);
	}

	public function test_log_level_should_be_info_when_debug()
	{
		$config = new Config(array('debug' => TRUE));
		$this->assertEquals(Logger::INFO, $config->log_level);
	}

	public function test_log_level_should_be_debug_when_not_debug()
	{
		$config = new Config(array('debug' => FALSE));
		$this->assertEquals(Logger::DEBUG, $config->log_level);
	}

}