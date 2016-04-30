<?php

namespace Honeybadger;

/**
 * Tests Honeybadger\Backtrace.
 *
 * @group honeybadger
 * @group honeybadger.integrations
 */
class SlimTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();

        $sender = $this->getMock('Honeybadger\Sender');
        $sender->expects($this->any())
            ->method('send_to_honeybadger')
            ->will($this->returnValue('12345'));

        // Add our sender mock to the environment.
        Honeybadger::$sender = $sender;
    }

    public function tearDown()
    {
        Honeybadger::$sender = new Sender;

        parent::tearDown();
    }

    public function build_middleware(array $options = array())
    {
        return new Slim($options);
    }

    public function test_init_uses_app_mode()
    {
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/index.php',
            'PATH_INFO' => '/foo'
        ));
        $app = new \Slim\Slim(array(
            'mode' => 'testing',
        ));

        $mw = $this->build_middleware();
        $mw->setApplication($app);
        $mw->setNextMiddleware($app);
        $mw->call();

        $this->assertEquals('testing', Honeybadger::$config->environment_name);
    }

    public function test_init_uses_framework()
    {
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/index.php',
            'PATH_INFO' => '/foo'
        ));
        $app = new \Slim\Slim(array(
            'mode' => 'testing',
        ));

        $mw = $this->build_middleware();
        $mw->setApplication($app);
        $mw->setNextMiddleware($app);
        $mw->call();

        $this->assertTrue(
            preg_match('/^Slim: [0-9\.]+$/', Honeybadger::$config->framework) === 1
        );
    }

    public function test_init_uses_app_logger()
    {
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/index.php',
            'PATH_INFO' => '/foo'
        ));
        $app = new \Slim\Slim(array(
            'mode' => 'testing',
        ));

        $mw = $this->build_middleware();
        $mw->setApplication($app);
        $mw->setNextMiddleware($app);
        $mw->call();

        $this->assertEquals(Honeybadger::$logger->logger, $app->getLog());
    }

    /**
     * @expectedException  Exception
     */
    public function test_call_should_rethrow_errors()
    {
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/index.php',
            'PATH_INFO' => '/foo'
        ));
        $app = new \Slim\Slim(array(
            'mode' => 'development',
        ));

        $app->get('/foo', function () {
            throw new \Exception('bleh! x.x');
        });

        $mw = $this->build_middleware();
        $mw->setApplication($app);
        $mw->setNextMiddleware($app);
        $mw->call();
    }

    public function test_call_should_set_error_id_in_env()
    {
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/index.php',
            'PATH_INFO' => '/foo'
        ));
        $app = new \Slim\Slim(array(
            'mode' => 'production',
        ));

        $app->get('/foo', function () {
            throw new \Exception('bleh! x.x');
        });

        $mw = $this->build_middleware();
        $mw->setApplication($app);
        $mw->setNextMiddleware($app);

        try {
            $mw->call();
        } catch (\Exception $e) {
            // noop
        }

        $env = $app->environment();

        $this->assertEquals('12345', $env['honeybadger.error_id']);
    }

    public function test_notify_honeybadger_is_skipped_for_ignored_user_agents()
    {
        Honeybadger::$config->ignore_user_agents[] = 'Internet Explorer';

        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/index.php',
            'PATH_INFO' => '/foo',
            'USER_AGENT' => 'Internet Explorer',
        ));
        $app = new \Slim\Slim(array(
            'mode' => 'production',
        ));

        $app->get('/foo', function () {
            throw new \Exception('bleh! x.x');
        });

        $mw = $this->build_middleware();
        $mw->setApplication($app);
        $mw->setNextMiddleware($app);

        try {
            $mw->call();
        } catch (\Exception $e) {
            // noop
        }

        $env = $app->environment();

        $this->assertNull($env['honeybadger.error_id']);
    }

    public function test_call_should_replace_placeholder()
    {
        \Slim\Environment::mock(array(
            'SCRIPT_NAME' => '/index.php',
            'PATH_INFO' => '/foo',
        ));
        $app = new \Slim\Slim(array(
            'mode' => 'production',
        ));

        $app->get('/foo', function () use ($app) {
            $app->response()->body('<!-- HONEYBADGER ERROR -->');
            throw new \Exception('dedz');
        });

        $mw = $this->build_middleware();
        $mw->setApplication($app);
        $mw->setNextMiddleware($app);

        try {
            $mw->call();
        } catch (\Exception $e) {
            // noop
        }

        $this->assertEquals('Honeybadger Error 12345', $app->response()->body());
    }

} // End SlimTest