<?php

namespace Honeybadger\Tests;

use DateTime;
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
        $eventsDispatcher = $this->createMock(BulkEventDispatcher::class);
        $eventsDispatcher
            ->expects($this->once())
            ->method('addEvent')
            ->with([
                'event_type' => 'log',
                'ts' => (new DateTime())->format(DATE_ATOM),
                'message' => 'Test message',
            ]);

        $client = HoneybadgerClient::new([
            new Response(201),
        ]);
        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'events' => [
                'enabled' => true,
            ],
        ], $client->make(), $eventsDispatcher);

        $badger->event('log', ['message' => 'Test message']);
    }

    /** @test */
    public function it_adds_ts_to_event_payload() {
        $eventsDispatcher = $this->createMock(BulkEventDispatcher::class);
        $eventsDispatcher
            ->expects($this->once())
            ->method('addEvent')
            ->with([
                'ts' => (new DateTime())->format(DATE_ATOM),
                'message' => 'Test message',
            ]);

        $client = HoneybadgerClient::new([
            new Response(201),
        ]);
        $badger = Honeybadger::new([
            'api_key' => 'asdf',
            'events' => [
                'enabled' => true,
            ],
        ], $client->make(), $eventsDispatcher);
        $badger->event(['message' => 'Test message']);
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
}
