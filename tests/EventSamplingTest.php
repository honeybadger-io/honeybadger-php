<?php

namespace Honeybadger\Tests;

use Honeybadger\BulkEventDispatcher;
use Honeybadger\Config;
use Honeybadger\Honeybadger;
use PHPUnit\Framework\TestCase;

class EventSamplingTest extends TestCase
{
    /** @test */
    public function it_samples_events_based_on_sample_rate()
    {
        // Create a mock client
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);

        // Create a custom event dispatcher that tracks events
        $eventsDispatcher = new class(new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true,
                'sample_rate' => 0 // No events should be sent
            ]
        ]), $client) extends BulkEventDispatcher {
            public $events = [];

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };

        // Create Honeybadger instance with 0% sampling rate
        $badger = new Honeybadger([
            'api_key' => '1234',
            'events' => [
                'enabled' => true,
                'sample_rate' => 0 // No events should be sent
            ]
        ], null, $eventsDispatcher);

        // Send an event
        $badger->event('log', ['message' => 'Test message']);

        // No events should be in the queue
        $this->assertCount(0, $eventsDispatcher->events);

        // Now create a new instance with 100% sampling rate
        $eventsDispatcher = new class(new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true,
                'sample_rate' => 100 // All events should be sent
            ]
        ]), $client) extends BulkEventDispatcher {
            public $events = [];

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };

        $badger = new Honeybadger([
            'api_key' => '1234',
            'events' => [
                'enabled' => true,
                'sample_rate' => 100 // All events should be sent
            ]
        ], null, $eventsDispatcher);

        // Send an event
        $badger->event('log', ['message' => 'Test message']);

        // Event should be in the queue
        $this->assertCount(1, $eventsDispatcher->events);
    }

    /** @test */
    public function it_samples_consistently_based_on_requestId()
    {
        // Create a mock client
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);

        // Create a custom event dispatcher that tracks events
        $eventsDispatcher = new class(new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true,
                'sample_rate' => 50 // 50% of events should be sent
            ]
        ]), $client) extends BulkEventDispatcher {
            public $events = [];

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };

        // Create Honeybadger instance with 50% sampling rate
        $badger = new Honeybadger([
            'api_key' => '1234',
            'events' => [
                'enabled' => true,
                'sample_rate' => 50 // 50% of events should be sent
            ]
        ], null, $eventsDispatcher);

        // Create a requestId that will be sampled (using a known value that will pass the CRC32 check)
        $sampledRequestId = 'sampled-request-123';

        // Create a requestId that will not be sampled (using a known value that will fail the CRC32 check)
        $notSampledRequestId = 'not-sampled-request-456';

        // Verify that all events with the same requestId are consistently sampled
        // First, send multiple events with the sampled requestId
        for ($i = 0; $i < 5; $i++) {
            $badger->event('log', [
                'message' => "Test message $i",
                'requestId' => $sampledRequestId
            ]);
        }

        // Check if events were sent consistently (either all or none)
        $sampledCount = count($eventsDispatcher->events);
        $this->assertTrue($sampledCount === 0 || $sampledCount === 5,
            "Expected either 0 or 5 events to be sampled, got $sampledCount");

        // Reset the events
        $eventsDispatcher->events = [];

        // Now send multiple events with the other requestId
        for ($i = 0; $i < 5; $i++) {
            $badger->event('log', [
                'message' => "Test message $i",
                'requestId' => $notSampledRequestId
            ]);
        }

        // Check if events were sent consistently (either all or none)
        $sampledCount = count($eventsDispatcher->events);
        $this->assertTrue($sampledCount === 0 || $sampledCount === 5,
            "Expected either 0 or 5 events to be sampled, got $sampledCount");
    }

    /** @test */
    public function it_respects_sample_rate_override_in_event_metadata()
    {
        // Create a mock client
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);

        // Create a custom event dispatcher that tracks events
        $eventsDispatcher = new class(new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true,
                'sample_rate' => 0 // No events should be sent by default
            ]
        ]), $client) extends BulkEventDispatcher {
            public $events = [];

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };

        // Create Honeybadger instance with 0% sampling rate
        $badger = new Honeybadger([
            'api_key' => '1234',
            'events' => [
                'enabled' => true,
                'sample_rate' => 0 // No events should be sent by default
            ]
        ], null, $eventsDispatcher);

        // Send an event with no override - should not be sent
        $badger->event('log', ['message' => 'This event should not be sent']);

        // Send an event with override to 100% - should be sent
        $badger->event('log', [
            'message' => 'This event should be sent',
            '_hb' => ['sample_rate' => 100]
        ]);

        // Only the second event should be in the queue
        $this->assertCount(1, $eventsDispatcher->events);
        $this->assertEquals('This event should be sent', $eventsDispatcher->events[0]['message']);
    }

    /** @test */
    public function it_removes_hb_metadata_before_sending_event()
    {
        // Create a mock client
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);

        // Create a custom event dispatcher that tracks events
        $eventsDispatcher = new class(new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true,
                'sample_rate' => 100 // All events should be sent
            ]
        ]), $client) extends BulkEventDispatcher {
            public $events = [];

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };

        // Create Honeybadger instance
        $badger = new Honeybadger([
            'api_key' => '1234',
            'events' => [
                'enabled' => true,
                'sample_rate' => 100
            ]
        ], null, $eventsDispatcher);

        // Send an event with _hb metadata
        $badger->event('log', [
            'message' => 'Test message',
            '_hb' => [
                'sample_rate' => 100,
                'other_metadata' => 'value'
            ]
        ]);

        // Verify the event was sent
        $this->assertCount(1, $eventsDispatcher->events);

        // Verify the _hb metadata was removed
        $this->assertArrayNotHasKey('_hb', $eventsDispatcher->events[0]);

        // Verify other fields are still present
        $this->assertEquals('Test message', $eventsDispatcher->events[0]['message']);
        $this->assertEquals('log', $eventsDispatcher->events[0]['event_type']);
    }

    /** @test */
    public function it_runs_sampling_after_before_event_callbacks()
    {
        // Create a mock client
        $client = $this->createMock(\Honeybadger\HoneybadgerClient::class);

        // Create a custom event dispatcher that tracks events
        $eventsDispatcher = new class(new Config([
            'api_key' => '1234',
            'events' => [
                'enabled' => true,
                'sample_rate' => 0 // No events should be sent by default
            ]
        ]), $client) extends BulkEventDispatcher {
            public $events = [];

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };

        // Create Honeybadger instance with 0% sampling rate
        $badger = new Honeybadger([
            'api_key' => '1234',
            'events' => [
                'enabled' => true,
                'sample_rate' => 0 // No events should be sent by default
            ]
        ], null, $eventsDispatcher);

        // Register a before_event callback that adds the _hb metadata to override sampling
        $badger->beforeEvent(function (&$event) {
            $event['_hb'] = ['sample_rate' => 100]; // Override to always send
            return true;
        });

        // Send an event
        $badger->event('log', ['message' => 'This event should be sent']);

        // The event should be in the queue because the callback added the override
        $this->assertCount(1, $eventsDispatcher->events);
        $this->assertEquals('This event should be sent', $eventsDispatcher->events[0]['message']);

        // The _hb metadata should be removed
        $this->assertArrayNotHasKey('_hb', $eventsDispatcher->events[0]);
    }
}
