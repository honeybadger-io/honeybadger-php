<?php

namespace Honeybadger\Tests;

use Exception;
use GuzzleHttp\Psr7\Response;
use Honeybadger\BulkEventDispatcher;
use Honeybadger\Config;
use Honeybadger\Honeybadger;
use Honeybadger\Tests\Mocks\HoneybadgerClient;
use PHPUnit\Framework\TestCase;

class BeforeHandlerTest extends TestCase
{
    /** @test */
    public function it_registers_multiple_before_notify_handlers() {
        $handler1Called = false;
        $handler2Called = false;

        $client = HoneybadgerClient::new([
            new Response(201),
        ]);
        $honeybadger = new Honeybadger([
            'api_key' => '1234',
        ], $client->make());

        $honeybadger->beforeNotify(function (&$notice) use (&$handler1Called) {
            $handler1Called = true;
        });

        $honeybadger->beforeNotify(function (&$notice) use (&$handler2Called) {
            $handler2Called = true;
        });

        $honeybadger->notify(new Exception('test'));

        $this->assertTrue($handler1Called);
        $this->assertTrue($handler2Called);
    }

    /** @test */
    public function it_registers_multiple_before_insights_event_handlers() {
        $handler1Called = false;
        $handler2Called = false;

        $config = new Config([
            'api_key' => 'asdf',
            'events' => [
                'enabled' => true,
            ],
        ]);
        $client = $this->createPartialMock(\Honeybadger\HoneybadgerClient::class, ['events', 'makeClient']);
        $eventsDispatcher = new BulkEventDispatcher($config, $client);
        $honeybadger = Honeybadger::new($config->all(), $client->makeClient(), $eventsDispatcher);

        $honeybadger->beforeEvent(function (&$event) use (&$handler1Called) {
            $handler1Called = true;
        });

        $honeybadger->beforeEvent(function (&$event) use (&$handler2Called) {
            $handler2Called = true;
        });

        $honeybadger->event('log', ['message' => 'Test message']);
        $this->assertTrue($eventsDispatcher->hasEvents());

        $this->assertTrue($handler1Called);
        $this->assertTrue($handler2Called);
    }

    /** @test */
    public function it_modifies_the_notice_before_sending() {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);
        $honeybadger = new Honeybadger([
            'api_key' => '1234',
        ], $client->make());

        $honeybadger->beforeNotify(function (&$notice) {
            $this->assertEquals('test', $notice['error']['message']);
            $notice['error']['message'] = 'Modified message';
        });

        $honeybadger->notify(new Exception('test'));

        $notification = $client->requestBody();
        $this->assertCount(1, $client->calls());
        $this->assertEquals('Modified message', $notification['error']['message']);
    }

    /** @test */
    public function it_skips_sending_the_notice() {
        $client = HoneybadgerClient::new([
            new Response(201),
        ]);
        $honeybadger = new Honeybadger([
            'api_key' => '1234',
        ], $client->make());

        $honeybadger->beforeNotify(function (&$notice) {
            return false;
        });

        $honeybadger->notify(new Exception('test'));

        $this->assertCount(0, $client->calls());
    }

    /** @test */
    public function it_modifies_the_insights_event_before_sending() {
        $config = new Config([
            'api_key' => 'asdf',
            'events' => [
                'enabled' => true,
            ],
        ]);
        $hbClientMock = $this->createMock(\Honeybadger\HoneybadgerClient::class);

        // the actual check is here
        $expectedEvent = [
            'event_type' => 'log',
            'ts' => (new \DateTime())->format(DATE_RFC3339_EXTENDED),
            'message' => 'Test message',
            'request-id' => 'some-id',
        ];
        $hbClientMock->expects($this->once())->method('events')->with([$expectedEvent]);

        $dispatcher = new BulkEventDispatcher($config, $hbClientMock);
        $honeybadger = Honeybadger::new($config->all(), $hbClientMock->makeClient(), $dispatcher);

        $honeybadger->beforeEvent(function (&$event) {
            $requestId = $event['requestId'];
            unset($event['requestId']);
            $event['request-id'] = $requestId;
        });

        $honeybadger->event($expectedEvent['event_type'], [
            'ts' => $expectedEvent['ts'],
            'requestId' => $expectedEvent['request-id'],
            'message' => $expectedEvent['message'],
        ]);
        $dispatcher->flushEvents();
    }

    /** @test */
    public function it_skips_sending_the_insights_event() {
        $config = new Config([
            'api_key' => 'asdf',
            'events' => [
                'enabled' => true,
            ],
        ]);
        $client = $this->createPartialMock(\Honeybadger\HoneybadgerClient::class, ['events', 'makeClient']);
        $eventsDispatcher = new BulkEventDispatcher($config, $client);
        $honeybadger = Honeybadger::new($config->all(), $client->makeClient(), $eventsDispatcher);

        $honeybadger->beforeEvent(function ($event) use (&$handler1Called) {
            return false;
        });

        $honeybadger->event('log', ['message' => 'Test message']);
        $this->assertFalse($eventsDispatcher->hasEvents());
    }
}
