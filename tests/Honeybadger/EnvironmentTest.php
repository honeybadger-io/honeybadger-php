<?php

namespace Honeybadger;

/**
 * Tests Honeybadger\Environment.
 *
 * @group honeybadger
 */
class EnvironmentTest extends TestCase
{

    protected $_environment_default = [
        '_COOKIE' => [
            'PHPSESSID' => '5jo4beb11n218lr1p0ekdpc916',
            '__utma'    => '1234567890.1234567890.1234567890.1234567890.1234567890.12',
        ],
    ];

    public function test_factory_should_return_instance_of_environment()
    {
        $this->assertTrue(Environment::factory() instanceof Environment);
    }

    public function test_should_use_standard_server_superglobals_when_not_supplied_data()
    {
        $environment = Environment::factory()->asArray();
        $this->assertNotEmpty($environment);
    }

    public function test_should_remove_non_standard_variables_when_not_supplied_data()
    {
        $this->setEnvironment(
            [
                '_SERVER' => [
                    'DATABASE_URL'  => 'postgres://root:p4ssw0rd@localhost/some_db',
                    'PASSWORD_SALT' => 'abcdefghijklmnopqrstuvwxyz',
                ],
                '_COOKIE' => [],
            ]
        );

        $environment = Environment::factory()->asArray();
        $this->assertFalse(isset($environment['DATABASE_URL']));
        $this->assertFalse(isset($environment['PASSWORD_SALT']));
    }

    public function test_should_not_remove_non_standard_variables_matching_http_headers()
    {
        $variables = [
            'PHP_SELF'             => '/var/www/index.php',
            'argv'                 => ['foo', 'bar', 'baz'],
            'argc'                 => 3,
            'GATEWAY_INTERFACE'    => 'CGI 2.0',
            'SERVER_ADDR'          => '127.0.0.1',
            'SERVER_NAME'          => 'localhost',
            'SERVER_SOFTWARE'      => 'Nginx',
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'REQUEST_METHOD'       => 'POST',
            'REQUEST_TIME'         => 123456,
            'REQUEST_TIME_FLOAT'   => 123456.789,
            'QUERY_STRING'         => 'foo=bar&baz[0]=2',
            'DOCUMENT_ROOT'        => '/var/www',
            'HTTPS'                => 'on',
            'REMOTE_ADDR'          => '127.0.0.1',
            'REMOTE_HOST'          => 'localhost',
            'REMOTE_PORT'          => 23415,
            'REMOTE_USER'          => 'admin',
            'REDIRECT_REMOTE_USER' => 'what?',
            'SCRIPT_FILENAME'      => '/var/www/index.php',
            'SERVER_ADMIN'         => 'root',
            'SERVER_PORT'          => 443,
            'SERVER_SIGNATURE'     => 'Nginx v0.8.1-dev',
            'PATH_TRANSLATED'      => 'again, what?',
            'SCRIPT_NAME'          => 'see SCRIPT_FILENAME?',
            'REQUEST_URI'          => '/show/me/something',
            'PHP_AUTH_DIGEST'      => 'asldfhgerlig;asdv',
            'PHP_AUTH_USER'        => 'admin',
            'PHP_AUTH_PW'          => 'test123',
            'AUTH_TYPE'            => 'basic',
            'PATH_INFO'            => '/var/www/index.php/show/me/something',
            'ORIG_PATH_INFO'       => '/',
        ];

        $this->setEnvironment(
            [
                '_SERVER' => $variables,
                '_COOKIE' => [],
            ]
        );

        $environment = Environment::factory()->asArray();

        $this->assertEquals($variables, $environment);
    }

    public function test_should_include_http_headers_when_not_supplied_data()
    {
        $headers = [
            'HTTP_X_API_KEY'    => '123abc',
            'HTTP_ACCEPT'       => 'application/json',
            'HTTP_CONTENT_TYPE' => 'text/plain; charset=utf-16',
            'HTTP_HOST'         => 'example.com',
            'HTTP_USER_AGENT'   => 'cURL',
        ];

        $this->setEnvironment(
            [
                '_SERVER' => $headers,
                '_COOKIE' => [],
            ]
        );

        $environment = Environment::factory()->asArray();

        $this->assertEquals($headers, $environment);
    }

    public function test_should_include_cookies_when_not_supplied_data()
    {
        $cookies = [
            'password' => 'smart people put sensitive data in plain text cookies',
        ];

        $this->setEnvironment(
            [
                '_COOKIE' => $cookies,
            ]
        );

        $environment = Environment::factory()->asArray();

        $this->assertEquals($cookies, $environment['rack.request.cookie_hash']);
    }

    public function test_protocol_should_be_http_when_https_blank()
    {
        $this->assertEquals('http', Environment::factory([])->protocol());
    }

    public function test_protocol_should_be_http_when_https_off()
    {
        $this->assertEquals(
            'http', Environment::factory(
            [
                'HTTPS' => 'off',
            ]
        )->protocol()
        );
    }

    public function test_protocol_should_be_https_when_https_on()
    {
        $this->assertEquals(
            'https', Environment::factory(
            [
                'HTTPS' => 'on',
            ]
        )->protocol()
        );
    }

    public function provider_https_on()
    {
        return [
            [
                'always',
            ],
            [
                'sometimes',
            ],
            [
                'never',
            ],
            [
                'whenever',
            ],
            [
                'maybe',
            ],
            [
                'mostly',
            ],
        ];
    }

    /**
     * @dataProvider provider_https_on
     */
    public function test_protocol_should_be_https_when_https_not_blank($value)
    {
        $this->assertEquals(
            'https', Environment::factory(
            [
                'HTTPS' => $value,
            ]
        )->protocol()
        );
    }

    public function test_is_secure()
    {
        $this->assertTrue(
            Environment::factory(
                [
                    'HTTPS' => 'on',
                ]
            )->isSecure()
        );

        $this->assertFalse(
            Environment::factory(
                [
                    'HTTPS' => 'off',
                ]
            )->isSecure()
        );
    }

    public function test_host_uses_server_name_when_http_host_unavailable()
    {
        $this->assertEquals(
            'example.com', Environment::factory(
            [
                'SERVER_NAME' => 'example.com',
            ]
        )->host()
        );
    }

    public function test_host_prefers_http_host()
    {
        $this->assertEquals(
            'foo.net', Environment::factory(
            [
                'SERVER_NAME' => 'example.com',
                'HTTP_HOST'   => 'foo.net',
            ]
        )->host()
        );
    }

    public function test_port_should_return_server_port()
    {
        $this->assertEquals(
            '123', Environment::factory(
            [
                'SERVER_PORT' => '123',
            ]
        )->port()
        );
    }

    public function test_port_should_detect_default_when_missing()
    {
        $this->assertEquals(
            80, Environment::factory(
            [
                'HTTPS' => 'off',
            ]
        )->port()
        );

        $this->assertEquals(
            443, Environment::factory(
            [
                'HTTPS' => 'on',
            ]
        )->port()
        );
    }

    public function test_non_standard_port_when_ssl()
    {
        $this->assertTrue(
            Environment::factory(
                [
                    'HTTPS'       => 'on',
                    'SERVER_PORT' => 123,
                ]
            )->isNonStandardPort()
        );

        $this->assertFalse(
            Environment::factory(
                [
                    'HTTPS'       => 'on',
                    'SERVER_PORT' => 443,
                ]
            )->isNonStandardPort()
        );
    }

    public function test_non_standard_port_when_http()
    {
        $this->assertTrue(
            Environment::factory(
                [
                    'HTTPS'       => 'off',
                    'SERVER_PORT' => 456,
                ]
            )->isNonStandardPort()
        );

        $this->assertFalse(
            Environment::factory(
                [
                    'HTTPS'       => 'off',
                    'SERVER_PORT' => 80,
                ]
            )->isNonStandardPort()
        );
    }

    public function test_url_uses_environment_when_present()
    {
        $env = Environment::factory(
            [
                'url' => 'http://example.com/',
            ]
        );

        $this->assertEquals('http://example.com/', $env['url']);
    }

    public function test_url_returns_combined_protocol_host_uri_query_string()
    {
        $env = Environment::factory(
            [
                'REQUEST_URI'  => '/foo/bar/xyz?one=1&two=2&three=3',
                'SCRIPT_NAME'  => '/foo/index.php',
                'HTTPS'        => 'on',
                'HTTP_HOST'    => 'www.example.com',
                'QUERY_STRING' => 'one=1&two=2&three=3',
            ]
        );

        $this->assertEquals('https://www.example.com/foo/bar/xyz?one=1&two=2&three=3', $env['url']);
    }

    public function test_url_adds_port_when_non_standard()
    {
        $env = Environment::factory(
            [
                'REQUEST_URI'  => '/foo/bar/xyz?one=1&two=2&three=3',
                'SCRIPT_NAME'  => '/foo/index.php',
                'HTTPS'        => '',
                'HTTP_HOST'    => 'www.example.com',
                'QUERY_STRING' => 'one=1&two=2&three=3',
                'SERVER_PORT'  => '123',
            ]
        );

        $this->assertEquals('http://www.example.com:123/foo/bar/xyz?one=1&two=2&three=3', $env['url']);
    }

    public function test_url_returns_null_when_empty_host_and_path()
    {
        $env = Environment::factory([]);

        $this->assertNull($env->url);
    }

    public function test_http_keys_can_be_filtered()
    {
      Honeybadger::$config->filteredHttpEnviromentKeys = ['HTTP_SESSION_ID'];

      $_SERVER['HTTP_SESSION_ID'] = 'bar';

      $env = Environment::factory();

      $this->assertNull($env['HTTP_SESSION_ID']);
    }
}
