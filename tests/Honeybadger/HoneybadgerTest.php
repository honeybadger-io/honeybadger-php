<?php

namespace Honeybadger;

/**
 * Tests Honeybadger.
 *
 * @group honeybadger
 */
class HoneybadgerTest extends TestCase
{

    protected $context = [
        'user' => [
            'id'   => 123,
            'name' => 'Gabriel Evans',
        ],
    ];

    public function setUp()
    {
        parent::setUp();
        Honeybadger::resetContext($this->context);
    }

    public function tearDown()
    {
        parent::tearDown();
        Honeybadger::resetContext();
    }

    public function test_initialized_with_void_logger()
    {
        $this->restoreEnvironment();
        Honeybadger::init();
        $this->assertTrue(Honeybadger::$logger instanceof Logger\Void);
    }

    public function test_configured()
    {
        $this->restoreEnvironment();
        Honeybadger::configure([]);
        $this->assertTrue(Honeybadger::$config instanceof Config);
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
        Honeybadger::context(
            [
                'user'   => [
                    'id'   => 123,
                    'name' => 'Gabriel Evans',
                ],
                'device' => 'iPhone',
            ]
        );

        $this->assertEquals(
            [
                'user'   => [
                    'id'   => 123,
                    'name' => 'Gabriel Evans',
                ],
                'device' => 'iPhone',
            ],
            Honeybadger::context()
        );
    }

    public function test_context_returns_data()
    {
        $this->assertEquals($this->context, Honeybadger::context());
    }

    public function test_reset_context_should_empty_context()
    {
        Honeybadger::resetContext();
        $this->assertEmpty(Honeybadger::context());
    }

    public function test_reset_context_should_return_empty_array()
    {
        $this->assertEmpty(Honeybadger::resetContext());
    }

    public function test_should_report_environment_info()
    {
        Honeybadger::reportEnvironmentInfo();
        $entry = Honeybadger::$logger->lastEntry();

        $this->assertEquals('** [Honeybadger] Environment info: ' . Honeybadger::environmentInfo(), $entry['message']);
    }

    public function test_environment_info_should_include_php_version()
    {
        $this->assertTrue(
            strpos(
                Honeybadger::environmentInfo(),
                phpversion()
            ) !== false
        );
    }

    public function test_environment_info_should_include_framework()
    {
        $this->assertTrue(
            strpos(
                Honeybadger::environmentInfo(),
                Honeybadger::$config->framework
            ) !== false
        );
    }

    public function test_environment_info_should_include_environment_name()
    {
        $this->assertTrue(
            strpos(
                Honeybadger::environmentInfo(),
                Honeybadger::$config->environment_name
            ) !== false
        );
    }

    public function test_environment_info_should_exclude_framework_when_none()
    {
        Honeybadger::$config->framework = null;

        $this->assertFalse(
            strpos(Honeybadger::environmentInfo(), ' []')
        );
    }

    public function test_environment_info_should_exclude_environment_name_when_none()
    {
        Honeybadger::$config->environment_name = null;

        $this->assertFalse(
            strpos(Honeybadger::environmentInfo(), ' []')
        );
    }

    public function test_report_response_body_should_log_supplied_response_body()
    {
        Honeybadger::reportResponseBody("don't care!");
        $entry = Honeybadger::$logger->lastEntry();

        $this->assertEquals(
            "** [Honeybadger] Response from Honeybadger:\ndon't care!",
            $entry['message']
        );
    }

    /**
     * TODO: Do more in-depth testing.
     */
    public function test_notify_should_return_null_when_not_public()
    {
        Honeybadger::$config->environment_name = 'development';
        $this->assertEmpty(Honeybadger::notify($this->buildException()));
    }

    public function test_notify_should_return_id_when_delivered()
    {
        Honeybadger::$config->environment_name = 'production';

        if (!Honeybadger::$config->api_key) {
            return $this->markTestSkipped('No API key configured.');
        }

        $this->assertNotEmpty(Honeybadger::notify($this->buildException()));
    }

    public function test_notify_should_accept_array_for_exception()
    {
        Honeybadger::$config->environment_name = 'production';

        if (!Honeybadger::$config->api_key) {
            return $this->markTestSkipped('No API key configured.');
        }

        $this->assertNotEmpty(
            Honeybadger::notify(
                [
                    'error_message' => 'There is none.',
                ]
            )
        );
    }

    public function test_notify_or_ignore_notifies_when_not_ignored()
    {
        Honeybadger::$config->environment_name = 'production';

        if (!Honeybadger::$config->api_key) {
            return $this->markTestSkipped('No API key configured.');
        }

        $this->assertNotEmpty(
            Honeybadger::notifyOrIgnore(
                $this->buildException()
            )
        );
    }

    public function test_notify_or_ignore_does_not_notify_when_ignored()
    {
        Honeybadger::$config->ignore = ['Exception'];
        $this->assertEmpty(
            Honeybadger::notifyOrIgnore(
                $this->buildException()
            )
        );
    }
}