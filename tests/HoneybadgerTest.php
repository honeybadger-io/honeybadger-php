<?php

namespace Honeybadger\Tests;

use Exception;
use GuzzleHttp\Psr7\Response;
use Honeybadger\BulkEventDispatcher;
use Honeybadger\Config;
use Honeybadger\Exceptions\ServiceException;
use Honeybadger\Handlers\ErrorHandler;
use Honeybadger\Handlers\ExceptionHandler;
use Honeybadger\Honeybadger;
use Honeybadger\Tests\Mocks\HoneybadgerClient;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class HoneybadgerTest extends TestCase
{
    /** @test */
    public function it_builds_a_backtrace()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $response = $badger->notify(new Exception('Test exception', 0, new Exception('Nested Exception')));

        $notification = $client->requestBody();

        $this->assertEquals([
            'name' => 'honeybadger-php',
            'version' => Honeybadger::VERSION,
            'url' => 'https://github.com/honeybadger-io/honeybadger-php',
        ], $notification['notifier']);

        $this->assertEquals('Test exception', $notification['error']['message']);
        $this->assertEquals(Exception::class, $notification['error']['class']);

        $this->assertArrayHasKey('backtrace', $notification['error']);
        $this->assertArrayHasKey('server', $notification);
    }

    /** @test */
    public function it_allows_modification_of_notifier_by_config()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'notifier' => [
                'name' => 'Honeybadger FUTURE',
                'url' => 'https://honeybadger.io/awesome-notifier',
                'version' => 66,
            ],
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $badger->context('foo', 'bar');

        $response = $badger->notify(new Exception('Test exception'));

        $notification = $client->requestBody();

        $this->assertEquals([
            'name' => 'Honeybadger FUTURE',
            'url' => 'https://honeybadger.io/awesome-notifier',
            'version' => 66,
        ], $notification['notifier']);
    }

    /** @test */
    public function it_accepts_and_sends_context()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $badger->context('foo', 'bar');

        $badger->notify(new Exception('Test exception'));
        $notification = $client->requestBody();
        $this->assertEquals(['foo' => 'bar'], $notification['request']['context']);
    }

    /** @test */
    public function it_accepts_and_sends_multiple_pieces_of_context()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $badger->context([
            'foo' => 'bar',
            'another' => 'context',
        ]);

        $response = $badger->notify(new Exception('Test exception'));

        $notification = $client->requestBody();

        $this->assertEquals([
            'foo' => 'bar',
            'another' => 'context',
        ], $notification['request']['context']);
    }

    /** @test */
    public function it_accepts_and_sends_chained_contexts()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $badger->context([
            'foo' => 'bar',
            'another' => 'context',
        ])->context([
            'chained-foo' => 'chained-bar',
            'chained-another' => 'chained-context',
        ]);

        $response = $badger->notify(new Exception('Test exception'));

        $notification = $client->requestBody();

        $this->assertEquals([
            'foo' => 'bar',
            'another' => 'context',
            'chained-foo' => 'chained-bar',
            'chained-another' => 'chained-context',
        ], $notification['request']['context']);
    }

    /** @test */
    public function it_json_encodes_empty_request_data_properly()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $badger->notify(new Exception('Test exception'));
        $requestBody = $client->calls()[0]['request']->getBody()->getContents();
        $this->assertStringContainsString('"context":{}', $requestBody);
        $this->assertStringContainsString('"params":{}', $requestBody);
        $this->assertStringContainsString('"session":{}', $requestBody);
    }

    /** @test */
    public function it_filters_and_includes_environment_keys_by_config()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $_SERVER['HOSTNAME'] = 'HONEYBADGER';
        $_ENV['APP_ENV'] = 'HONEYBADGER_TEST';

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'environment' => [
                'filter' => ['DOCUMENT_ROOT'],
                'include' => ['APP_ENV', 'HOSTNAME'],
            ],
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $response = $badger->notify(new Exception('Test exception'));

        $notification = $client->requestBody();

        $this->assertArrayNotHasKey('DOCUMENT_ROOT', $notification['server']);
        $this->assertEquals('HONEYBADGER', $notification['request']['cgi_data']['HOSTNAME']);
    }

    /** @test */
    public function it_adds_environment_name_via_config()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'environment_name' => 'testing',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $response = $badger->notify(new Exception('Test exception'));

        $notification = $client->requestBody();

        $this->assertEquals('testing', $notification['server']['environment_name']);
    }

    /** @test */
    public function it_sends_a_checkin_using_id()
    {
        $client = HoneybadgerClient::new([
            new Response(200),
        ]);

        Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make())->checkin('1234');

        $request = $client->request()[0]['request'];

        $this->assertEquals('v1/check_in/1234', $request->getUri()->getPath());
    }

    /** @test */
    public function it_sends_a_checkin_using_slug() {
        $client = HoneybadgerClient::new([
            //checkinsClient->getProjectId()
            new Response(200, [], json_encode([
                'project' => [
                    [
                        'id' => 'p1234',
                    ],
                ]
            ])),
            //checkinsClient->listForProject()
            new Response(200, [], json_encode([
                'results' => [
                    [
                        'id' => 'c1234',
                        'slug' => 'a-simple-check-in',
                        'schedule_type' => 'simple',
                        'report_period' => 60,
                    ],
                ]
            ])),
            //client->checkin()
            new Response(200)
        ]);

        Honeybadger::new([
            'api_key' => 'asdf',
            'personal_auth_token' => 'asdfasdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
            'checkins' => [
                [
                    'slug' => 'a-simple-check-in',
                    'schedule_type' => 'simple',
                    'report_period' => 60,
                ],
            ]
        ], $client->make())->checkin('a-simple-check-in');

        $request = $client->getLatestRequest();
        $this->assertEquals('v1/check_in/asdf/a-simple-check-in', $request->getUri()->getPath());
    }

    /** @test */
    public function it_set_global_handlers_by_default()
    {
        Honeybadger::new(['api_key' => '1234']);

        $this->assertInstanceOf(
            ExceptionHandler::class,
            set_exception_handler(null)[0]
        );

        $this->assertInstanceOf(
            ErrorHandler::class,
            set_error_handler(null)[0]
        );
    }

    /** @test */
    public function global_handlers_can_be_disabled()
    {
        $this->markTestSkipped('Need to review later, something in CI causes this to fail');

        Honeybadger::new([
            'api_key' => '1234',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ]);

        $errorHandler = set_error_handler(null);

        $errorHandler = is_array($errorHandler)
            ? $errorHandler[0]
            : $errorHandler;

        $this->assertNotInstanceOf(
            ExceptionHandler::class,
            $errorHandler
        );

        $this->assertNotInstanceOf(
            ErrorHandler::class,
            $errorHandler
        );
    }

    /** @test */
    public function it_throws_an_exception_for_exception_notifications_based_on_the_status_code()
    {
        $this->expectException(ServiceException::class);

        $client = HoneybadgerClient::new([
            new Response(500),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $badger->notify(new Exception('Test exception'));
    }

    /** @test */
    public function it_throws_an_exception_for_custom_notifications_based_on_the_status_code()
    {
        $this->expectException(ServiceException::class);

        $client = HoneybadgerClient::new([
            new Response(500),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $badger->customNotification([]);
    }

    /** @test */
    public function it_throws_an_exception_for_checkins_based_on_the_status_code()
    {
        $this->expectException(ServiceException::class);

        $client = HoneybadgerClient::new([
            new Response(500),
        ]);

        Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make())->checkin('1234');
    }

    /** @test */
    public function it_excludes_exceptions()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
            'excluded_exceptions' => [
                InvalidArgumentException::class,
            ],
        ], $client->make());

        $response = $badger->notify(new InvalidArgumentException('Test exception'));

        $this->assertEmpty($client->calls());
    }

    /** @test */
    public function it_sends_a_custom_payload()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $badger->context('foo', 'bar');

        $response = $badger->customNotification([
            'title'   => 'Special Error',
            'message' => 'Special Error: this was a super special case',
        ]);

        $request = $client->requestBody();

        $this->assertEquals([
            'error' => [
                'class' => 'Special Error',
                'message' => 'Special Error: this was a super special case',
            ],
            'request' => [
                'context' => [
                    'foo'  => 'bar',
                ],
            ],
        ], array_only($request, ['error', 'request']));

        $this->assertArrayHasKey('notifier', $request);
    }

    /** @test */
    public function allows_for_custom_endpoint()
    {
        $host = 'invalid.url.reallyinvalid';

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'endpoint' => "http://$host/invalid",
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ]);

        try {
            $badger->notify(new Exception('Test exception'));
        } catch (ServiceException $e) {
            $this->assertStringContainsString($host, $e->getPrevious()->getMessage());
        }

    }

    /** @test */
    public function it_doesnt_report_service_exceptions()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $response = $badger->notify(ServiceException::generic());

        $this->assertEmpty($client->calls());
    }

    /** @test */
    public function it_returns_the_error_id()
    {
        $client = HoneybadgerClient::new([
            new Response(201, [], json_encode([
                'id' => 'asdf123',
            ])),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $response = $badger->customNotification([
            'title'   => 'Special Error',
            'message' => 'Special Error: this was a super special case',
        ]);

        $this->assertEquals([
            'id' => 'asdf123',
        ], $response);
    }

    /** @test */
    public function exceptions_do_not_get_reported_when_config_key_is_empty()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => '',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $response = $badger->notify(new InvalidArgumentException('Test exception'));

        $this->assertEmpty($client->calls());
    }

    /** @test */
    public function custom_notifications_do_not_get_reported_when_config_key_is_empty()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => '',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $response = $badger->customNotification([
            'title' => 'Test Notification',
            'message' => 'Test notification message',
        ]);

        $this->assertEmpty($client->calls());
    }

    /** @test */
    public function a_raw_notification_can_be_sent()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $badger->context('foo', 'bar');

        $response = $badger->rawNotification(function ($config, $context) {
            $this->assertEquals('asdf', $config['api_key']);
            $this->assertEquals('bar', $context['foo']);

            return [
                'error' => [
                    'class' => 'Special Error',
                    'message' => 'Special Error: this was a super special case',
                    'tags' => ['foo'],
                ],
                'request' => [
                    'context' => ['baz' => 'qux'],
                ],
            ];
        });

        $request = $client->requestBody();

        $this->assertEquals([
            'error' => [
                'class' => 'Special Error',
                'message' => 'Special Error: this was a super special case',
                'tags' => ['foo'],
            ],
            'request' => [
                'context' => [
                    'baz' => 'qux',
                ],
            ],
        ], array_only($request, ['error', 'request']));

        $this->assertArrayHasKey('notifier', $request);
    }

    /** @test */
    public function raw_notification_notifier_name_field_is_required()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        try {
            $response = $badger->rawNotification(function ($config, $context) {
                return [
                    'notifier' => [],
                    'error' => [
                        'class' => 'Special Error',
                    ],
                ];
            });
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('The notification notifier.name field is required', $e->getMessage());
        }
    }

    /** @test */
    public function raw_notification_error_class_field_is_required()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        try {
            $response = $badger->rawNotification(function ($config, $context) {
                return [
                    'error' => [],
                ];
            });
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('The notification error.class field is required', $e->getMessage());
        }
    }

    /** @test */
    public function context_can_be_reset()
    {
        $badger = Honeybadger::new([
            'api_key' => null,
        ]);

        $badger->context('foo', 'bar');

        $this->assertEquals([
            'foo' => 'bar',
        ], $badger->getContext()->all());

        $badger->resetContext();

        $this->assertEmpty($badger->getContext()->all());
    }

    /** @test */
    public function no_data_is_sent_if_reporting_is_disabled()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);
        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
            'report_data' => false,
        ], $client->make());
        $badger->rawNotification(function ($config, $context) {
            return [
                'error' => [
                    'class' => 'Foo',
                ],
            ];
        });
        $badger->customNotification([]);
        $badger->notify(new \Exception('Whoops!'));
        $this->assertEmpty($client->request());
    }

    /** @test */
    public function it_can_send_grouping_options()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $options = [
            'component' => 'sample',
            'action' => 'index',
            'fingerprint' => '97sdff976',
            'tags' => ['user', 'core'],
        ];
        $badger->notify(new Exception('Test exception'), null, $options);

        $notification = $client->requestBody();

        $this->assertEquals($options['component'], $notification['request']['component']);
        $this->assertEquals($options['action'], $notification['request']['action']);
        $this->assertEquals($options['fingerprint'], $notification['error']['fingerprint']);
        $this->assertEquals($options['tags'], $notification['error']['tags']);
    }

    /** @test */
    public function context_and_action_can_be_set()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        $badger->setComponent('HomeController');
        $badger->setAction('index');

        $badger->notify(new Exception('Test exception'));

        $notification = $client->requestBody();

        $this->assertEquals('HomeController', $notification['request']['component']);
        $this->assertEquals('index', $notification['request']['action']);
        $this->assertArrayNotHasKey('component', $notification['request']['context']);
        $this->assertArrayNotHasKey('action', $notification['request']['context']);
    }

    /** @test */
    public function auto_adds_breadcrumb_for_notice_if_enabled()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());
        $badger->setComponent('HomeController')
            ->setAction('index')
            ->notify(new Exception('Test exception'));

        $notification = $client->requestBody();

        $this->assertTrue($notification['breadcrumbs']['enabled']);
        $this->assertCount(1, $notification['breadcrumbs']['trail']);
        $this->assertEquals('Honeybadger Notice', $notification['breadcrumbs']['trail'][0]['message']);
        $this->assertEquals('notice', $notification['breadcrumbs']['trail'][0]['category']);
        $this->assertInstanceOf(\DateTime::class, date_create($notification['breadcrumbs']['trail'][0]['timestamp']));
        $this->assertEquals([
            'message' => 'Test exception',
            'name' => Exception::class,
        ], $notification['breadcrumbs']['trail'][0]['metadata']);
    }

    /** @test */
    public function can_add_custom_breadcrumbs_if_enabled()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        sleep(1);
        $eventTime = date('c');
        $badger->addBreadcrumb('A thing', ['some' => 'data'], 'render')
            ->setComponent('HomeController')
            ->setAction('index')
            ->notify(new Exception('Test exception'));

        $notification = $client->requestBody();

        $this->assertTrue($notification['breadcrumbs']['enabled']);
        $this->assertCount(2, $notification['breadcrumbs']['trail']);

        $this->assertEquals('A thing', $notification['breadcrumbs']['trail'][0]['message']);
        $this->assertEquals('render', $notification['breadcrumbs']['trail'][0]['category']);
        $this->assertEquals($eventTime, $notification['breadcrumbs']['trail'][0]['timestamp']);
        $this->assertEquals(['some' => 'data'], $notification['breadcrumbs']['trail'][0]['metadata']);

        $this->assertEquals('Honeybadger Notice', $notification['breadcrumbs']['trail'][1]['message']);
        $this->assertEquals('notice', $notification['breadcrumbs']['trail'][1]['category']);
        $this->assertInstanceOf(\DateTime::class, date_create($notification['breadcrumbs']['trail'][1]['timestamp']));
        $this->assertEquals([
            'message' => 'Test exception',
            'name' => Exception::class,
        ], $notification['breadcrumbs']['trail'][1]['metadata']);
    }

    /** @test */
    public function does_not_add_breadcrumbs_with_empty_message()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        sleep(1);
        $badger
            ->addBreadcrumb('', ['this will not be' => 'sent'], 'render')
            ->setComponent('HomeController')
            ->setAction('index')
            ->notify(new Exception('Test exception'));

        $notification = $client->requestBody();

        $this->assertTrue($notification['breadcrumbs']['enabled']);

        // only the notice breadcrumb should be sent
        $this->assertCount(1, $notification['breadcrumbs']['trail']);

        $noticeBreadcrumb = $notification['breadcrumbs']['trail'][0];
        $this->assertEquals('Honeybadger Notice', $noticeBreadcrumb['message']);
        $this->assertEquals('notice', $noticeBreadcrumb['category']);
        $this->assertInstanceOf(\DateTime::class, date_create($noticeBreadcrumb['timestamp']));
        $this->assertEquals([
            'message' => 'Test exception',
            'name' => Exception::class,
        ], $noticeBreadcrumb['metadata']);
    }

    /** @test */
    public function wont_send_breadcrumbs_if_disabled()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
            'breadcrumbs' => [
                'enabled' => false,
            ],
        ], $client->make());
        $badger->setComponent('HomeController')
            ->setAction('index')
            ->notify(new Exception('Test exception'));

        $notification = $client->requestBody();

        $this->assertIsArray($notification['breadcrumbs']);
        $this->assertFalse($notification['breadcrumbs']['enabled']);
        $this->assertCount(0, $notification['breadcrumbs']['trail']);
    }

    /** @test */
    public function wont_send_event_if_disabled() {
        $eventsDispatcher = $this->createMock(BulkEventDispatcher::class);
        $eventsDispatcher->expects($this->never())->method('addEvent');

        $client = HoneybadgerClient::new([
            new Response(201),
        ]);
        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'events' => [
                'enabled' => false,
                'bulk_threshold' => 1,
            ],
        ], $client->make(), $eventsDispatcher);

        $badger->event('log', ['message' => 'Test message']);
    }

    /** @test */
    public function it_adds_event_type_and_ts_to_event_payload() {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);
        $badger->event('log', ['message' => 'Test message']);

        $this->assertCount(1, $eventsDispatcher->events);

        $event = $eventsDispatcher->events[0];
        $this->assertArrayHasKey('ts', $event);
        $this->assertEquals('log', $event['event_type']);
        $this->assertEquals('Test message', $event['message']);
    }

    /** @test */
    public function it_adds_ts_to_event_payload() {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        $badger->event(['message' => 'Test message']);
        $this->assertCount(1, $eventsDispatcher->events);

        $event = $eventsDispatcher->events[0];
        $this->assertArrayHasKey('ts', $event);
        $this->assertArrayNotHasKey('event_type', $event);
        $this->assertEquals('Test message', $event['message']);
    }

    /** @test */
    public function it_queues_an_event() {
        $config = new Config([
            'api_key' => 'asdf',
            'events' => [
                'enabled' => true,
            ],
        ]);
        $client = $this->createPartialMock(\Honeybadger\HoneybadgerClient::class, ['events', 'makeClient']);
        $eventsDispatcher = new BulkEventDispatcher($config, $client);
        $badger = Honeybadger::new($config->all(), $client->makeClient(), $eventsDispatcher);

        $badger->event('log', ['message' => 'Test message']);
        $this->assertTrue($eventsDispatcher->hasEvents());
    }

    /** @test */
    public function it_queues_an_event_with_payload_only() {
        $config = new Config([
            'api_key' => 'asdf',
            'events' => [
                'enabled' => true,
            ],
        ]);
        $client = $this->createPartialMock(\Honeybadger\HoneybadgerClient::class, ['events', 'makeClient']);
        $eventsDispatcher = new BulkEventDispatcher($config, $client);
        $badger = Honeybadger::new($config->all(), $client->makeClient(), $eventsDispatcher);

        $badger->event(['event_type' => 'log', 'message' => 'Test message']);
        $this->assertTrue($eventsDispatcher->hasEvents());
    }

    /** @test */
    public function wont_send_event_if_payload_is_empty() {
        $config = new Config([
            'api_key' => 'asdf',
            'events' => [
                'enabled' => true,
            ],
        ]);
        $client = $this->createPartialMock(\Honeybadger\HoneybadgerClient::class, ['events', 'makeClient']);
        $eventsDispatcher = new BulkEventDispatcher($config, $client);
        $badger = Honeybadger::new($config->all(), $client->makeClient(), $eventsDispatcher);

        $badger->event([]);
        $this->assertFalse($eventsDispatcher->hasEvents());
    }

    /** @test */
    public function it_flushes_events() {
        $config = new Config([
            'api_key' => 'asdf',
            'events' => [
                'enabled' => true,
            ],
        ]);
        $client = $this->createPartialMock(\Honeybadger\HoneybadgerClient::class, ['events', 'makeClient']);
        $eventsDispatcher = new BulkEventDispatcher($config, $client);
        $badger = Honeybadger::new($config->all(), $client->makeClient(), $eventsDispatcher);

        $badger->event('log', ['message' => 'Test message']);
        $this->assertTrue($eventsDispatcher->hasEvents());
        $badger->flushEvents();
        $this->assertFalse($eventsDispatcher->hasEvents());
    }

    /** @test */
    public function it_sets_event_context_with_single_key_value_pair()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        $result = $badger->eventContext('user_id', 123);

        // Should return self for method chaining
        $this->assertSame($badger, $result);

        // Verify context was set by sending an event and checking the result
        $badger->event('test_event', ['test' => 'data']);

        $this->assertCount(1, $eventsDispatcher->events);
        $event = $eventsDispatcher->events[0];
        $this->assertEquals(123, $event['user_id']);
    }

    /** @test */
    public function it_sets_event_context_with_array_input()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        $contextData = [
            'user_id' => 123,
            'session_id' => 'abc123',
            'feature_flag' => true,
        ];

        $result = $badger->eventContext($contextData);

        // Should return self for method chaining
        $this->assertSame($badger, $result);

        // Verify context was set by sending an event and checking the result
        $badger->event('test_event', ['test' => 'data']);

        $this->assertCount(1, $eventsDispatcher->events);
        $event = $eventsDispatcher->events[0];
        $this->assertEquals(123, $event['user_id']);
        $this->assertEquals('abc123', $event['session_id']);
        $this->assertTrue($event['feature_flag']);
    }

    /** @test */
    public function it_preserves_data_types_in_event_context()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        $contextData = [
            'string_value' => 'test',
            'integer_value' => 42,
            'float_value' => 3.14,
            'boolean_true' => true,
            'boolean_false' => false,
            'null_value' => null,
            'array_value' => ['nested' => 'data'],
        ];

        $badger->eventContext($contextData);

        // Verify data types are preserved by sending an event and checking the result
        $badger->event('test_event', ['test' => 'data']);

        $this->assertCount(1, $eventsDispatcher->events);
        $event = $eventsDispatcher->events[0];

        $this->assertSame('test', $event['string_value']);
        $this->assertSame(42, $event['integer_value']);
        $this->assertSame(3.14, $event['float_value']);
        $this->assertTrue($event['boolean_true']);
        $this->assertFalse($event['boolean_false']);
        $this->assertNull($event['null_value']);
        $this->assertEquals(['nested' => 'data'], $event['array_value']);
    }

    /** @test */
    public function it_clears_populated_event_context()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        // Set some event context data
        $badger->eventContext([
            'user_id' => 123,
            'session_id' => 'abc123',
            'feature_flag' => true,
        ]);

        // Verify context was set by sending an event
        $badger->event('test_event_before', ['test' => 'before']);

        $this->assertCount(1, $eventsDispatcher->events);
        $eventBefore = $eventsDispatcher->events[0];
        $this->assertEquals(123, $eventBefore['user_id']);
        $this->assertEquals('abc123', $eventBefore['session_id']);
        $this->assertTrue($eventBefore['feature_flag']);

        // Clear the context
        $result = $badger->clearEventContext();

        // Should return self for method chaining
        $this->assertSame($badger, $result);

        // Verify context was cleared by sending another event
        $badger->event('test_event_after', ['test' => 'after']);

        $this->assertCount(2, $eventsDispatcher->events);
        $eventAfter = $eventsDispatcher->events[1];
        $this->assertArrayNotHasKey('user_id', $eventAfter);
        $this->assertArrayNotHasKey('session_id', $eventAfter);
        $this->assertArrayNotHasKey('feature_flag', $eventAfter);
        $this->assertEquals('after', $eventAfter['test']);
    }

    /** @test */
    public function it_merges_event_context_into_event_payload()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        // Set event context
        $badger->eventContext([
            'user_id' => 123,
            'session_id' => 'abc123',
            'environment' => 'test'
        ]);

        // Send an event
        $badger->event('user_action', ['action' => 'login', 'success' => true]);

        $this->assertCount(1, $eventsDispatcher->events);
        $event = $eventsDispatcher->events[0];

        // Verify context data was merged
        $this->assertEquals(123, $event['user_id']);
        $this->assertEquals('abc123', $event['session_id']);
        $this->assertEquals('test', $event['environment']);

        // Verify event-specific data is present
        $this->assertEquals('user_action', $event['event_type']);
        $this->assertEquals('login', $event['action']);
        $this->assertTrue($event['success']);

        // Verify timestamp is present
        $this->assertArrayHasKey('ts', $event);
    }

    /** @test */
    public function it_gives_event_data_precedence_over_context_data()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        // Set event context with conflicting keys
        $badger->eventContext([
            'user_id' => 123,
            'action' => 'context_action',
            'priority' => 'low'
        ]);

        // Send an event with conflicting keys
        $badger->event('user_action', [
            'action' => 'event_action',
            'priority' => 'high',
            'event_specific' => 'data'
        ]);

        $this->assertCount(1, $eventsDispatcher->events);
        $event = $eventsDispatcher->events[0];

        // Event data should take precedence
        $this->assertEquals('event_action', $event['action']);
        $this->assertEquals('high', $event['priority']);

        // Context data should be present for non-conflicting keys
        $this->assertEquals(123, $event['user_id']);

        // Event-specific data should be present
        $this->assertEquals('data', $event['event_specific']);
        $this->assertEquals('user_action', $event['event_type']);
    }

    /** @test */
    public function it_merges_nested_data_structures_correctly()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        // Set event context with nested data
        $badger->eventContext([
            'user' => [
                'id' => 123,
                'name' => 'John Doe',
                'preferences' => ['theme' => 'dark']
            ],
            'metadata' => [
                'source' => 'context',
                'version' => '1.0'
            ]
        ]);

        // Send an event with nested data that conflicts
        $badger->event('user_action', [
            'user' => [
                'id' => 456,  // This should override context
                'email' => 'john@example.com'  // This should be added
            ],
            'metadata' => [
                'source' => 'event',  // This should override context
                'timestamp' => '2023-01-01'  // This should be added
            ],
            'action' => 'login'
        ]);

        $this->assertCount(1, $eventsDispatcher->events);
        $event = $eventsDispatcher->events[0];

        // Event data should completely override context arrays
        $this->assertEquals([
            'id' => 456,
            'email' => 'john@example.com'
        ], $event['user']);

        $this->assertEquals([
            'source' => 'event',
            'timestamp' => '2023-01-01'
        ], $event['metadata']);

        // Event-specific data should be present
        $this->assertEquals('login', $event['action']);
        $this->assertEquals('user_action', $event['event_type']);
    }

    /** @test */
    public function it_handles_empty_event_context()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        // Don't set any event context
        $badger->event('user_action', ['action' => 'login', 'success' => true]);

        $this->assertCount(1, $eventsDispatcher->events);
        $event = $eventsDispatcher->events[0];

        // Only event data and timestamp should be present
        $this->assertEquals('user_action', $event['event_type']);
        $this->assertEquals('login', $event['action']);
        $this->assertTrue($event['success']);
        $this->assertArrayHasKey('ts', $event);

        // Should only have the expected keys
        $expectedKeys = ['ts', 'event_type', 'action', 'success'];
        $actualKeys = array_keys($event);
        sort($expectedKeys);
        sort($actualKeys);
        $this->assertEquals($expectedKeys, $actualKeys);
    }

    /** @test */
    public function it_handles_null_values_in_context_and_events()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        // Set event context with null values
        $badger->eventContext([
            'user_id' => null,
            'session_id' => 'abc123',
            'optional_field' => null
        ]);

        // Send an event with null values
        $badger->event('user_action', [
            'action' => null,
            'success' => true,
            'error_message' => null
        ]);

        $this->assertCount(1, $eventsDispatcher->events);
        $event = $eventsDispatcher->events[0];

        // Null values should be preserved
        $this->assertNull($event['user_id']);
        $this->assertEquals('abc123', $event['session_id']);
        $this->assertNull($event['optional_field']);
        $this->assertNull($event['action']);
        $this->assertTrue($event['success']);
        $this->assertNull($event['error_message']);
    }

    /** @test */
    public function it_preserves_data_types_during_merging()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        // Set event context with various data types
        $badger->eventContext([
            'string_value' => 'context_string',
            'integer_value' => 42,
            'float_value' => 3.14,
            'boolean_value' => true,
            'array_value' => ['nested' => 'context_data']
        ]);

        // Send an event with various data types
        $badger->event('user_action', [
            'event_string' => 'event_string',
            'event_integer' => 100,
            'event_float' => 2.71,
            'event_boolean' => false,
            'event_array' => ['nested' => 'event_data']
        ]);

        $this->assertCount(1, $eventsDispatcher->events);
        $event = $eventsDispatcher->events[0];

        // Context data types should be preserved
        $this->assertSame('context_string', $event['string_value']);
        $this->assertSame(42, $event['integer_value']);
        $this->assertSame(3.14, $event['float_value']);
        $this->assertTrue($event['boolean_value']);
        $this->assertEquals(['nested' => 'context_data'], $event['array_value']);

        // Event data types should be preserved
        $this->assertSame('event_string', $event['event_string']);
        $this->assertSame(100, $event['event_integer']);
        $this->assertSame(2.71, $event['event_float']);
        $this->assertFalse($event['event_boolean']);
        $this->assertEquals(['nested' => 'event_data'], $event['event_array']);
    }

    /** @test */
    public function it_merges_context_after_timestamp_but_before_event_data()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        // Set event context with a 'ts' key to test precedence
        $badger->eventContext([
            'ts' => 'context_timestamp',
            'user_id' => 123
        ]);

        // Send an event with a 'ts' key to test precedence
        $badger->event('user_action', [
            'ts' => 'event_timestamp',
            'action' => 'login'
        ]);

        $this->assertCount(1, $eventsDispatcher->events);
        $event = $eventsDispatcher->events[0];

        // Event 'ts' should take precedence over context 'ts'
        $this->assertEquals('event_timestamp', $event['ts']);

        // Context data should be present
        $this->assertEquals(123, $event['user_id']);

        // Event data should be present
        $this->assertEquals('login', $event['action']);
        $this->assertEquals('user_action', $event['event_type']);
    }

    /** @test */
    public function it_persists_context_across_multiple_events()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        // Set event context
        $badger->eventContext([
            'user_id' => 123,
            'session_id' => 'abc123'
        ]);

        // Send first event
        $badger->event('user_login', ['success' => true]);

        // Send second event
        $badger->event('page_view', ['page' => '/dashboard']);

        // Send third event
        $badger->event('user_logout', ['duration' => 300]);

        $this->assertCount(3, $eventsDispatcher->events);

        // All events should have the context data
        foreach ($eventsDispatcher->events as $event) {
            $this->assertEquals(123, $event['user_id']);
            $this->assertEquals('abc123', $event['session_id']);
        }

        // Verify event-specific data
        $this->assertTrue($eventsDispatcher->events[0]['success']);
        $this->assertEquals('user_login', $eventsDispatcher->events[0]['event_type']);

        $this->assertEquals('/dashboard', $eventsDispatcher->events[1]['page']);
        $this->assertEquals('page_view', $eventsDispatcher->events[1]['event_type']);

        $this->assertEquals(300, $eventsDispatcher->events[2]['duration']);
        $this->assertEquals('user_logout', $eventsDispatcher->events[2]['event_type']);
    }

    /** @test */
    public function it_does_not_include_context_after_clearing()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        // Set event context
        $badger->eventContext([
            'user_id' => 123,
            'session_id' => 'abc123'
        ]);

        // Send first event (should include context)
        $badger->event('user_login', ['success' => true]);

        // Clear context
        $badger->clearEventContext();

        // Send second event (should not include context)
        $badger->event('page_view', ['page' => '/dashboard']);

        $this->assertCount(2, $eventsDispatcher->events);

        // First event should have context
        $this->assertEquals(123, $eventsDispatcher->events[0]['user_id']);
        $this->assertEquals('abc123', $eventsDispatcher->events[0]['session_id']);
        $this->assertTrue($eventsDispatcher->events[0]['success']);

        // Second event should not have context
        $this->assertArrayNotHasKey('user_id', $eventsDispatcher->events[1]);
        $this->assertArrayNotHasKey('session_id', $eventsDispatcher->events[1]);
        $this->assertEquals('/dashboard', $eventsDispatcher->events[1]['page']);
        $this->assertEquals('page_view', $eventsDispatcher->events[1]['event_type']);
    }

    /** @test */
    public function it_keeps_event_context_separate_from_notice_context()
    {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);

        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'handlers' => [
                'exception' => false,
                'error' => false,
            ],
        ], $client->make());

        // Set event context
        $badger->eventContext([
            'event_user_id' => 123,
            'event_session' => 'event_session_123'
        ]);

        // Set notice context
        $badger->context([
            'notice_user_id' => 456,
            'notice_session' => 'notice_session_456'
        ]);

        // Send a notice
        $badger->notify(new Exception('Test exception'));

        $notification = $client->requestBody();

        // Notice should only have notice context, not event context
        $this->assertEquals([
            'notice_user_id' => 456,
            'notice_session' => 'notice_session_456'
        ], $notification['request']['context']);

        // Event context should not be in the notice
        $this->assertArrayNotHasKey('event_user_id', $notification['request']['context']);
        $this->assertArrayNotHasKey('event_session', $notification['request']['context']);
    }

    /** @test */
    public function it_keeps_notice_context_separate_from_event_context()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        // Set notice context
        $badger->context([
            'notice_user_id' => 456,
            'notice_session' => 'notice_session_456'
        ]);

        // Set event context
        $badger->eventContext([
            'event_user_id' => 123,
            'event_session' => 'event_session_123'
        ]);

        // Send an event
        $badger->event('user_action', ['action' => 'login']);

        $this->assertCount(1, $eventsDispatcher->events);
        $event = $eventsDispatcher->events[0];

        // Event should only have event context, not notice context
        $this->assertEquals(123, $event['event_user_id']);
        $this->assertEquals('event_session_123', $event['event_session']);

        // Notice context should not be in the event
        $this->assertArrayNotHasKey('notice_user_id', $event);
        $this->assertArrayNotHasKey('notice_session', $event);

        // Event-specific data should be present
        $this->assertEquals('login', $event['action']);
        $this->assertEquals('user_action', $event['event_type']);
    }

    /** @test */
    public function it_makes_event_context_data_available_in_before_event_handlers()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        // Set event context
        $badger->eventContext([
            'user_id' => 123,
            'session_id' => 'abc123',
            'environment' => 'test'
        ]);

        // Track what data is available in the beforeEvent handler
        $handlerEventData = null;
        $badger->beforeEvent(function ($event) use (&$handlerEventData) {
            $handlerEventData = $event;
            return true; // Allow the event to proceed
        });

        // Send an event
        $badger->event('user_action', ['action' => 'login', 'success' => true]);

        // Verify the handler received the merged event data including context
        $this->assertNotNull($handlerEventData);

        // Context data should be available in the handler
        $this->assertEquals(123, $handlerEventData['user_id']);
        $this->assertEquals('abc123', $handlerEventData['session_id']);
        $this->assertEquals('test', $handlerEventData['environment']);

        // Event-specific data should be available in the handler
        $this->assertEquals('user_action', $handlerEventData['event_type']);
        $this->assertEquals('login', $handlerEventData['action']);
        $this->assertTrue($handlerEventData['success']);

        // Timestamp should be available in the handler
        $this->assertArrayHasKey('ts', $handlerEventData);

        // Verify the event was actually sent with the merged data
        $this->assertCount(1, $eventsDispatcher->events);
        $sentEvent = $eventsDispatcher->events[0];
        $this->assertEquals(123, $sentEvent['user_id']);
        $this->assertEquals('abc123', $sentEvent['session_id']);
        $this->assertEquals('test', $sentEvent['environment']);
        $this->assertEquals('login', $sentEvent['action']);
        $this->assertTrue($sentEvent['success']);
    }

    /** @test */
    public function it_allows_before_event_handlers_to_modify_merged_context_data()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        // Set event context
        $badger->eventContext([
            'user_id' => 123,
            'environment' => 'test'
        ]);

        // Add a beforeEvent handler that modifies the merged data
        $badger->beforeEvent(function (&$event) {
            // Modify context data
            $event['user_id'] = 456;
            // Add new data
            $event['modified_by_handler'] = true;
            // Modify event data
            $event['action'] = 'modified_login';
            return true;
        });

        // Send an event
        $badger->event('user_action', ['action' => 'login', 'success' => true]);

        // Verify the modifications were applied
        $this->assertCount(1, $eventsDispatcher->events);
        $event = $eventsDispatcher->events[0];

        // Handler modifications should be present
        $this->assertEquals(456, $event['user_id']); // Modified from 123
        $this->assertTrue($event['modified_by_handler']); // Added by handler
        $this->assertEquals('modified_login', $event['action']); // Modified from 'login'

        // Unmodified data should still be present
        $this->assertEquals('test', $event['environment']);
        $this->assertTrue($event['success']);
        $this->assertEquals('user_action', $event['event_type']);
    }

    /** @test */
    public function it_allows_before_event_handlers_to_block_events_with_context_data()
    {
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);
        $config = new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, \Honeybadger\HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $badger = new Honeybadger($config->all(), null, $eventsDispatcher);

        // Set event context
        $badger->eventContext([
            'user_id' => 123,
            'environment' => 'test'
        ]);

        // Add a beforeEvent handler that blocks events based on context data
        $badger->beforeEvent(function ($event) {
            // Block events for user_id 123
            return $event['user_id'] !== 123;
        });

        // Send an event - should be blocked
        $badger->event('user_action', ['action' => 'login', 'success' => true]);

        // Verify the event was blocked
        $this->assertCount(0, $eventsDispatcher->events);

        // Change context and try again
        $badger->eventContext(['user_id' => 456, 'environment' => 'test']);

        // Send another event - should not be blocked
        $badger->event('user_action', ['action' => 'login', 'success' => true]);

        // Verify the second event was sent
        $this->assertCount(1, $eventsDispatcher->events);
        $event = $eventsDispatcher->events[0];
        $this->assertEquals(456, $event['user_id']);
        $this->assertEquals('login', $event['action']);
    }
}
