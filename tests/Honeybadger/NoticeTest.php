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
        $config          = new Config;
        $config->api_key = 'abc123def456';

        return $config;
    }

    public function build_notice(array $args = [])
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

        $notice = Notice::factory([
                                      'exception' => $exception,
                                  ]);

        $expected = new Notice(Honeybadger::$config->merge([
                                                               'exception' => $exception,
                                                           ]));

        $this->assertEquals($expected, $notice);
    }

    public function test_should_use_parameters()
    {
        $notice = $this->build_notice([
                                          'parameters' => [
                                              'foo' => 'bar',
                                          ],
                                      ]);

        $this->assertEquals(['foo' => 'bar'], $notice->params);
    }

    public function test_should_set_component()
    {
        $notice = $this->build_notice([
                                          'component' => 'Welcome',
                                      ]);

        $this->assertEquals('Welcome', $notice->component);
    }

    public function test_should_set_controller_as_component()
    {
        $notice = $this->build_notice([
                                          'controller' => 'Products',
                                      ]);

        $this->assertEquals('Products', $notice->component);
    }

    public function test_should_set_controller_from_params()
    {
        $notice = $this->build_notice([
                                          'params' => [
                                              'controller' => 'Messages',
                                          ],
                                      ]);

        $this->assertEquals('Messages', $notice->component);
    }

    public function test_should_set_action()
    {
        $notice = $this->build_notice([
                                          'action' => 'index',
                                      ]);

        $this->assertEquals('index', $notice->action);
    }

    public function test_should_set_action_from_params()
    {
        $notice = $this->build_notice([
                                          'params' => [
                                              'action' => 'destroy',
                                          ],
                                      ]);

        $this->assertEquals('destroy', $notice->action);
    }

    public function test_should_set_exception()
    {
        $exception = new HoneybadgerError;

        $notice = $this->build_notice([
                                          'exception' => $exception,
                                      ]);

        $this->assertEquals($exception, $notice->exception);
    }

    public function test_should_set_backtrace_from_exception()
    {
        $exception = new HoneybadgerError;

        $notice = $this->build_notice([
                                          'exception' => $exception,
                                      ]);

        $backtrace = Backtrace::parse($exception->getTrace());
        $backtrace->lines[0]->source;

        $this->assertEquals($backtrace, $notice->backtrace);
    }

    public function test_should_set_error_class_from_exception()
    {
        $exception = new NonExistentProperty($this, 'foo');

        $notice = $this->build_notice([
                                          'exception' => $exception,
                                      ]);

        $this->assertEquals('Honeybadger\\Errors\\NonExistentProperty',
                            $notice->error_class);
    }

    public function test_should_set_error_message_from_exception()
    {
        $exception = new \Exception('This is a generic exception.');

        $notice = $this->build_notice([
                                          'exception' => $exception,
                                      ]);

        $this->assertEquals('Exception [ 0 ]: This is a generic exception.',
                            $notice->error_message);
    }

    public function test_should_set_backtrace_from_arguments()
    {
        $backtrace = [
            [
                'file'     => 'foo.php',
                'line'     => 42,
                'function' => 'process',
            ],
        ];

        $notice = $this->build_notice([
                                          'backtrace' => $backtrace,
                                      ]);

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
        $notice = $this->build_notice([
                                          'error_class' => 'ArgumentError',
                                      ]);

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

        $sender = $this->getMock('Honeybadger\Sender', [
            'sendToHoneybadger'
        ]);
        $sender->expects($this->once())
               ->method('sendToHoneybadger')
               ->with($this->equalTo($notice))
               ->will($this->returnValue('win'));

        $original_sender     = Honeybadger::$sender;
        Honeybadger::$sender = $sender;

        $this->assertEquals('win', $notice->deliver());

        Honeybadger::$sender = $original_sender;
    }

    public function test_should_format_as_array_with_correct_layout()
    {
        $exception = new \Exception('Something broke!');

        $raw_backtrace            = $exception->getTrace();
        $raw_backtrace[0]['file'] = path_to_fixture('MyClass.php');
        $raw_backtrace[0]['line'] = 1;

        $backtrace = Backtrace::parse($raw_backtrace);

        $data = [
            'backtrace'        => $raw_backtrace,
            'error_class'      => 'Exception',
            'error_message'    => 'Exception [ 0 ]: Something broke!',
            'url'              => 'https://example.com/orders',
            'component'        => 'Orders',
            'action'           => 'create',
            'params'           => [
                'line_item_ids' => [
                    123, 456, 789,
                ],
            ],
            'session'          => [
                'user_id' => 123
            ],
            'cgi_data'         => [
                'HTTP_HOST'      => 'example.com',
                'REQUEST_METHOD' => 'POST',
            ],
            'context'          => [
                'user' => ['name' => 'Joe Blow'],
            ],
            'project_root'     => '/var/www/application',
            'environment_name' => 'production',
        ];

        $notice = $this->build_notice($data);

        $this->assertEquals([
                                'notifier' => [
                                    'name'     => Honeybadger::NOTIFIER_NAME,
                                    'url'      => Honeybadger::NOTIFIER_URL,
                                    'version'  => Honeybadger::VERSION,
                                    'language' => 'php',
                                ],
                                'error'    => [
                                    'class'     => $data['error_class'],
                                    'message'   => $data['error_message'],
                                    'backtrace' => $backtrace->asArray(),
                                    'source'    => $backtrace->lines[0]->source,
                                ],
                                'request'  => [
                                    'url'       => $data['url'],
                                    'component' => $data['component'],
                                    'action'    => $data['action'],
                                    'params'    => $data['params'],
                                    'session'   => $data['session'],
                                    'cgi_data'  => $data['cgi_data'],
                                    'context'   => $data['context'],
                                ],
                                'server'   => [
                                    'project_root'     => $data['project_root'],
                                    'environment_name' => $data['environment_name'],
                                    'hostname'         => gethostname(),
                                ],
                            ], $notice->asArray());
    }

    public function test_extract_source_from_backtrace_should_prefer_application_lines()
    {
        $raw_backtrace = [
            [
                'file'     => FIXTURES_PATH . '/vendor/some_lib.php',
                'line'     => 58,
                'function' => 'die',
            ],
            [
                'file'     => FIXTURES_PATH . '/MyClass.php',
                'line'     => 12,
                'function' => 'does_amazing_things',
            ],
        ];

        $notice = $this->build_notice([
                                          'backtrace'    => $raw_backtrace,
                                          'project_root' => FIXTURES_PATH,
                                      ]);

        $line = Backtrace\Line::parse($raw_backtrace[1]);

        $this->assertEquals($line->source, $notice->source_extract);
    }

    public function test_should_not_set_session_data_when_send_request_session_is_false()
    {
        $notice = $this->build_notice([
                                          'send_request_session' => false,
                                          'session_data'         => [
                                              'message' => 'I am invisible.',
                                          ],
                                      ]);

        $this->assertEmpty($notice->session_data);
    }

    public function test_should_set_session_data_from_arguments()
    {
        $session = [
            'user_id' => 123,
        ];

        $notice = $this->build_notice([
                                          'session_data' => $session,
                                      ]);

        $this->assertEquals($session, $notice->session_data);
    }

    public function test_should_set_session_data_from_session_argument()
    {
        $session = [
            'cart' => [
                123, 456, 789,
            ]
        ];

        $notice = $this->build_notice([
                                          'session' => $session,
                                      ]);

        $this->assertEquals($session, $notice->session_data);
    }

    public function test_should_set_session_data_from_native_session()
    {
        if (isset($_SESSION)) {
            $original_session = $_SESSION;
        }

        $_SESSION = [
            'legacy' => true,
        ];

        $notice = $this->build_notice();

        if (isset($original_session)) {
            $_SESSION = $original_session;
        }

        $this->assertEquals($_SESSION, $notice->session_data);
    }

    public function test_url_is_detected()
    {
        $env = [
            'HTTP_HOST'    => 'example.com',
            'SERVER_PORT'  => '80',
            'SCRIPT_NAME'  => '/index.php',
            'REQUEST_URI'  => '/index.php/sessions/create',
            'PATH_INFO'    => '/sessions/create',
            'QUERY_STRING' => 'one=1&two=2&three=3',
            'HTTPS'        => '',
        ];

        $notice = $this->build_notice([
                                          'cgi_data' => $env,
                                      ]);

        $this->assertEquals('http://example.com/index.php/sessions/create?one=1&two=2&three=3', $notice->url);
    }

    public function test_should_be_ignored_when_in_ignore()
    {
        $notice = $this->build_notice([
                                          'exception' => new \Exception,
                                          'ignore'    => ['\\Exception'],
                                      ]);

        $this->assertTrue($notice->isIgnored());
    }

    public function test_should_be_ignored_when_filtered_with_callback()
    {
        $notice = $this->build_notice([
                                          'exception'         => new \Exception,
                                          'ignore_by_filters' => [
                                              function ($notice) {
                                                  return true;
                                              }
                                          ],
                                      ]);

        $this->assertTrue($notice->isIgnored());
    }
}