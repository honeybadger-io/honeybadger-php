<?php

namespace Honeybadger\Tests;

use Honeybadger\Environment;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    /** @test */
    public function environment_will_can_be_set_by_superglobal()
    {
        $_SERVER['SERVER_NAME'] = 'foo';
        $_SERVER['DOCUMENT_ROOT'] = 'bar';
        $env = (new Environment)->values();

        $this->assertEquals('foo', $env['SERVER_NAME']);
        $this->assertEquals('bar', $env['DOCUMENT_ROOT']);
    }

    /** @test */
    public function it_filters_non_whitelisted_keys()
    {
        $this->assertArrayNotHasKey(
            'FOO',
            (new Environment(['FOO' => 'bar']))->values()
        );
    }

    /** @test */
    public function it_allows_whitelisted_keys()
    {
        $this->assertEquals([
            'PHP_SELF',
            'argv',
            'argc',
            'GATEWAY_INTERFACE',
            'SERVER_ADDR',
            'SERVER_NAME',
            'SERVER_SOFTWARE',
            'SERVER_PROTOCOL',
            'REQUEST_METHOD',
            'REQUEST_TIME',
            'REQUEST_TIME_FLOAT',
            'QUERY_STRING',
            'DOCUMENT_ROOT',
            'HTTPS',
            'REMOTE_ADDR',
            'REMOTE_HOST',
            'REMOTE_PORT',
            'REMOTE_USER',
            'REDIRECT_REMOTE_USER',
            'SCRIPT_FILENAME',
            'SERVER_ADMIN',
            'SERVER_PORT',
            'SERVER_SIGNATURE',
            'PATH_TRANSLATED',
            'SCRIPT_NAME',
            'REQUEST_URI',
            'PHP_AUTH_DIGEST',
            'PHP_AUTH_USER',
            'PHP_AUTH_PW',
            'AUTH_TYPE',
            'PATH_INFO',
            'ORIG_PATH_INFO',
            'APP_ENV',
        ], Environment::KEY_WHITELIST);
    }

    /** @test */
    public function it_filters_additional_defined_keys()
    {
        $server = [
            'SERVER_NAME' => 'foo',
            'DOCUMENT_ROOT' => 'bar',
        ];

        $env = ['APP_KEY' => 'supersecret'];

        $environment = (new Environment($server))
            ->filterKeys(['SERVER_NAME'])
            ->values();

        $this->assertEquals('[FILTERED]', $environment['SERVER_NAME']);
    }

    /** @test */
    public function it_includes_additional_included_keys_to_be_defined()
    {
        $server = ['FOO' => 'bar'];
        $env = ['BAZ' => 'BAX'];

        $environment = (new Environment($server, $env))
            ->include(['FOO', 'BAZ'])
            ->values();

        $this->assertArrayHasKey('FOO', $environment);
        $this->assertArrayHasKey('BAZ', $environment);
    }

    /** @test */
    public function it_auto_includes_http_keys()
    {
        $server = ['HTTP_METHOD' => 'POST'];

        $environment = (new Environment($server))->values();

        $this->assertEquals('POST', $environment['HTTP_METHOD']);
    }

    /** @test */
    public function it_filters_default_values()
    {
        $server = ['HTTP_AUTHORIZATION' => 'Bearer 1234'];

        $environment = (new Environment($server))->values();

        $this->assertEquals('[FILTERED]', $environment['HTTP_AUTHORIZATION']);
    }
}
