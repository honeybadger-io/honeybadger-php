<?php

namespace Honeybadger\Tests;

use Honeybadger\Config;
use Honeybadger\Honeybadger;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /** @test */
    public function it_merges_configuration_values()
    {
        $config = (new Config([
            'api_key' => '1234',
            'app_endpoint' => 'https://eu-app.honeybadger.io',
        ]))->all();

        $this->assertArrayHasKey('service_exception_handler', $config);
        $this->assertArrayHasKey('events_exception_handler', $config);
        unset($config['service_exception_handler']);
        unset($config['events_exception_handler']);

        $this->assertEquals([
            'api_key' => '1234',
            'personal_auth_token' => null,
            'endpoint' => Honeybadger::API_URL,
            'app_endpoint' => 'https://eu-app.honeybadger.io',
            'notifier' => [
                'name' => 'honeybadger-php',
                'url' => 'https://github.com/honeybadger-io/honeybadger-php',
                'version' => Honeybadger::VERSION,
            ],
            'environment' => [
                'filter' => [],
                'include' => [],
            ],
            'request' => [
                'filter' => [],
            ],
            'version' => '',
            'hostname' => gethostname(),
            'project_root' => '',
            'environment_name' => 'production',
            'handlers' => [
                'exception' => true,
                'error' => true,
                'shutdown' => true,
            ],
            'client' => [
                'timeout' => 15,
                'proxy' => [],
                'verify' => true,
            ],
            'excluded_exceptions' => [],
            'capture_deprecations' => false,
            'report_data' => true,
            'vendor_paths' => [
                'vendor\/.*',
            ],
            'breadcrumbs' => [
                'enabled' => true,
            ],
            'checkins' => [],
            'events' => [
                'enabled' => false,
                'bulk_threshold' => 100,
                'dispatch_interval_seconds' => 2,
            ],
        ], $config);
    }

    /** @test */
    public function it_set_shutdown_handler_to_true_by_default()
    {
        $config = (new Config([
            'api_key' => '1234',
            'handlers' => [
                'exception' => true,
                'error' => true,
            ],
        ]))->all();

        $this->assertArrayHasKey('service_exception_handler', $config);
        $this->assertArrayHasKey('events_exception_handler', $config);
        unset($config['service_exception_handler']);
        unset($config['events_exception_handler']);

        $this->assertEquals([
            'api_key' => '1234',
            'personal_auth_token' => null,
            'endpoint' => Honeybadger::API_URL,
            'app_endpoint' => Honeybadger::APP_URL,
            'notifier' => [
                'name' => 'honeybadger-php',
                'url' => 'https://github.com/honeybadger-io/honeybadger-php',
                'version' => Honeybadger::VERSION,
            ],
            'environment' => [
                'filter' => [],
                'include' => [],
            ],
            'request' => [
                'filter' => [],
            ],
            'version' => '',
            'hostname' => gethostname(),
            'project_root' => '',
            'environment_name' => 'production',
            'handlers' => [
                'exception' => true,
                'error' => true,
                'shutdown' => true,
            ],
            'client' => [
                'timeout' => 15,
                'proxy' => [],
                'verify' => true,
            ],
            'excluded_exceptions' => [],
            'capture_deprecations' => false,
            'report_data' => true,
            'vendor_paths' => [
                'vendor\/.*',
            ],
            'breadcrumbs' => [
                'enabled' => true,
            ],
            'checkins' => [],
            'events' => [
                'enabled' => false,
                'bulk_threshold' => 100,
                'dispatch_interval_seconds' => 2,
            ],
        ], $config);
    }
}
