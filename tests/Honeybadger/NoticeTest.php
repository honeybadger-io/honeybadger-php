<?php

namespace Honeybadger;

use Honeybadger\Errors\HoneybadgerError;
use Honeybadger\Errors\NonExistentProperty;

/**
 * Tests Honeybadger\Notice.
 *
 * @group honeybadger
 */
class NoticeTest extends TestCase
{

    public function configure()
    {
        $config = new Config;
        $config->api_key = 'abc123def456';
        return $config;
    }

    public function build_notice(array $args = array())
    {
        if (isset($args['config'])) {
            $config = $args['config'];
            unset($args['config']);
        } else {
            $config = $this->configure();
        }

        return new Notice($config->merge($args));
    }

    public function test_factory_should_merge_config_options()
    {
        $exception = $this->build_exception();

        $notice = Notice::factory(array(
            'exception' => $exception,
        ));

        $expected = new Notice(Honeybadger::$config->merge(array(
            'exception' => $exception,
        )));

        $this->assertEquals($expected, $notice);
    }

    public function test_should_use_parameters()
    {
        $notice = $this->build_notice(array(
            'parameters' => array(
                'foo' => 'bar',
            ),
        ));

        $this->assertEquals(array('foo' => 'bar'), $notice->params);
    }

    public function test_should_set_component()
    {
        $notice = $this->build_notice(array(
            'component' => 'Welcome',
        ));

        $this->assertEquals('Welcome', $notice->component);
    }

    public function test_should_set_controller_as_component()
    {
        $notice = $this->build_notice(array(
            'controller' => 'Products',
        ));

        $this->assertEquals('Products', $notice->component);
    }

    public function test_should_set_controller_from_params()
    {
        $notice = $this->build_notice(array(
            'params' => array(
                'controller' => 'Messages',
            ),
        ));

        $this->assertEquals('Messages', $notice->component);
    }

    public function test_should_set_action()
    {
        $notice = $this->build_notice(array(
            'action' => 'index',
        ));

        $this->assertEquals('index', $notice->action);
    }

    public function test_should_set_action_from_params()
    {
        $notice = $this->build_notice(array(
            'params' => array(
                'action' => 'destroy',
            ),
        ));

        $this->assertEquals('destroy', $notice->action);
    }

    public function test_should_set_exception()
    {
        $exception = new HoneybadgerError;

        $notice = $this->build_notice(array(
            'exception' => $exception,
        ));

        $this->assertEquals($exception, $notice->exception);
    }

    public function test_should_set_backtrace_from_exception()
    {
        $exception = new HoneybadgerError;

        $notice = $this->build_notice(array(
            'exception' => $exception,
        ));

        $backtrace = Backtrace::parse($exception->getTrace());
        $backtrace->lines[0]->source;

        $this->assertEquals($backtrace, $notice->backtrace);
    }

    public function test_should_set_error_class_from_exception()
    {
        $exception = new NonExistentProperty($this, 'foo');

        $notice = $this->build_notice(array(
            'exception' => $exception,
        ));

        $this->assertEquals('Honeybadger\\Errors\\NonExistentProperty',
            $notice->error_class);
    }

    public function test_should_set_error_message_from_exception()
    {
        $exception = new \Exception('This is a generic exception.');

        $notice = $this->build_notice(array(
            'exception' => $exception,
        ));

        $this->assertEquals('Exception [ 0 ]: This is a generic exception.',
            $notice->error_message);
    }

    public function test_should_set_backtrace_from_arguments()
    {
        $backtrace = array(
            array(
                'file' => 'foo.php',
                'line' => 42,
                'function' => 'process',
            ),
        );

        $notice = $this->build_notice(array(
            'backtrace' => $backtrace,
        ));

        $backtrace = Backtrace::parse($backtrace);
        $backtrace->lines[0]->source;

        $this->assertEquals($backtrace, $notice->backtrace);
    }

    public function test_should_set_backtrace_from_debug_backtrace()
    {
        $notice = $this->build_notice();
        $this->assertTrue($notice->backtrace->hasLines());
    }

    public function test_should_set_error_class_from_arguments()
    {
        $notice = $this->build_notice(array(
            'error_class' => 'ArgumentError',
        ));

        $this->assertEquals('ArgumentError', $notice->error_class);
    }

    public function test_should_set_default_error_message()
    {
        $notice = $this->build_notice();

        $this->assertEquals('Notification', $notice->error_message);
    }

    public function test_should_deliver_to_sender_and_return_result()
    {
        $notice = $this->build_notice();

        $sender = $this->getMock('Honeybadger\Sender', array(
            'sendToHoneybadger'
        ));
        $sender->expects($this->once())
            ->method('sendToHoneybadger')
            ->with($this->equalTo($notice))
            ->will($this->returnValue('win'));

        $original_sender = Honeybadger::$sender;
        Honeybadger::$sender = $sender;

        $this->assertEquals('win', $notice->deliver());

        Honeybadger::$sender = $original_sender;
    }

    public function test_should_format_as_array_with_correct_layout()
    {
        $exception = new \Exception('Something broke!');

        $raw_backtrace = $exception->getTrace();
        $raw_backtrace[0]['file'] = path_to_fixture('MyClass.php');
        $raw_backtrace[0]['line'] = 1;

        $backtrace = Backtrace::parse($raw_backtrace);

        $data = array(
            'backtrace' => $raw_backtrace,
            'error_class' => 'Exception',
            'error_message' => 'Exception [ 0 ]: Something broke!',
            'url' => 'https://example.com/orders',
            'component' => 'Orders',
            'action' => 'create',
            'params' => array(
                'line_item_ids' => array(
                    123, 456, 789,
                ),
            ),
            'session' => array(
                'user_id' => 123
            ),
            'cgi_data' => array(
                'HTTP_HOST' => 'example.com',
                'REQUEST_METHOD' => 'POST',
            ),
            'context' => array(
                'user' => array('name' => 'Joe Blow'),
            ),
            'project_root' => '/var/www/application',
            'environment_name' => 'production',
        );

        $notice = $this->build_notice($data);

        $this->assertEquals(array(
            'notifier' => array(
                'name' => Honeybadger::NOTIFIER_NAME,
                'url' => Honeybadger::NOTIFIER_URL,
                'version' => Honeybadger::VERSION,
                'language' => 'php',
            ),
            'error' => array(
                'class' => $data['error_class'],
                'message' => $data['error_message'],
                'backtrace' => $backtrace->asArray(),
                'source' => $backtrace->lines[0]->source,
            ),
            'request' => array(
                'url' => $data['url'],
                'component' => $data['component'],
                'action' => $data['action'],
                'params' => $data['params'],
                'session' => $data['session'],
                'cgi_data' => $data['cgi_data'],
                'context' => $data['context'],
            ),
            'server' => array(
                'project_root' => $data['project_root'],
                'environment_name' => $data['environment_name'],
                'hostname' => gethostname(),
            ),
        ), $notice->asArray());
    }

    public function test_extract_source_from_backtrace_should_prefer_application_lines()
    {
        $raw_backtrace = array(
            array(
                'file' => FIXTURES_PATH . '/vendor/some_lib.php',
                'line' => 58,
                'function' => 'die',
            ),
            array(
                'file' => FIXTURES_PATH . '/MyClass.php',
                'line' => 12,
                'function' => 'does_amazing_things',
            ),
        );

        $notice = $this->build_notice(array(
            'backtrace' => $raw_backtrace,
            'project_root' => FIXTURES_PATH,
        ));

        $line = Backtrace\Line::parse($raw_backtrace[1]);

        $this->assertEquals($line->source, $notice->source_extract);
    }

    public function test_should_not_set_session_data_when_send_request_session_is_false()
    {
        $notice = $this->build_notice(array(
            'send_request_session' => false,
            'session_data' => array(
                'message' => 'I am invisible.',
            ),
        ));

        $this->assertEmpty($notice->session_data);
    }

    public function test_should_set_session_data_from_arguments()
    {
        $session = array(
            'user_id' => 123,
        );

        $notice = $this->build_notice(array(
            'session_data' => $session,
        ));

        $this->assertEquals($session, $notice->session_data);
    }

    public function test_should_set_session_data_from_session_argument()
    {
        $session = array(
            'cart' => array(
                123, 456, 789,
            )
        );

        $notice = $this->build_notice(array(
            'session' => $session,
        ));

        $this->assertEquals($session, $notice->session_data);
    }

    public function test_should_set_session_data_from_native_session()
    {
        if (isset($_SESSION)) {
            $original_session = $_SESSION;
        }

        $_SESSION = array(
            'legacy' => true,
        );

        $notice = $this->build_notice();

        if (isset($original_session)) {
            $_SESSION = $original_session;
        }

        $this->assertEquals($_SESSION, $notice->session_data);
    }

    public function test_url_is_detected()
    {
        $env = array(
            'HTTP_HOST' => 'example.com',
            'SERVER_PORT' => '80',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/index.php/sessions/create',
            'PATH_INFO' => '/sessions/create',
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'HTTPS' => '',
        );

        $notice = $this->build_notice(array(
            'cgi_data' => $env,
        ));

        $this->assertEquals('http://example.com/index.php/sessions/create?one=1&two=2&three=3', $notice->url);
    }

    public function test_should_be_ignored_when_in_ignore()
    {
        $notice = $this->build_notice(array(
            'exception' => new \Exception,
            'ignore' => array('\\Exception'),
        ));

        $this->assertTrue($notice->isIgnored());
    }

    public function test_should_be_ignored_when_filtered_with_callback()
    {
        $notice = $this->build_notice(array(
            'exception' => new \Exception,
            'ignore_by_filters' => array(
                function ($notice) {
                    return true;
                }
            ),
        ));

        $this->assertTrue($notice->isIgnored());
    }

}