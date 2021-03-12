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
        $config = (new Config(['api_key' => '1234']))->all();

        $this->assertArrayHasKey('service_exception_handler', $config);
        unset($config['service_exception_handler']);

        $this->assertEquals([
            'api_key' => '1234',
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
            ],
            'client' => [
                'timeout' => 0,
                'proxy' => [],
                'verify' => true,
            ],
            'excluded_exceptions' => [],
            'report_data' => true,
            'vendor_paths' => [
                'vendor\/.*',
            ],
            'breadcrumbs' => [
                'enabled' => true,
                'keep' => 40,
            ],
        ], $config);
    }
}
