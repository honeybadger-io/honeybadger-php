<?php

namespace Honeybadger\Tests;

use DateTime;
use Honeybadger\BulkEventDispatcher;
use Honeybadger\Config;
use Honeybadger\HoneybadgerClient;
use PHPUnit\Framework\TestCase;
use Mockery;

class BulkEventDispatcherTest extends TestCase {

    /** @test */
    public function it_initializes_the_bulk_event_dispatcher() {
        $config = new Config([
            'api_key' => 'hbp_ABC',
        ]);
        $hbMock = Mockery::mock(HoneybadgerClient::class)->makePartial();
        $dispatcher = new BulkEventDispatcher($config, $hbMock);
        $this->assertInstanceOf(BulkEventDispatcher::class, $dispatcher);
    }

    /** @test */
    public function it_adds_event_to_the_queue() {
        $config = new Config([
            'api_key' => 'hbp_ABC',
        ]);
        $hbMock = $this->createMock(HoneybadgerClient::class);
        $dispatcher = new BulkEventDispatcher($config, $hbMock);
        $dispatcher->addEvent(['event_type' => 'log', 'ts' => (new DateTime())->format(DATE_RFC3339_EXTENDED)]);
        $this->assertTrue($dispatcher->hasEvents());
    }

    /** @test */
    public function it_sends_events_when_threshold_is_reached() {
        $config = new Config([
            'api_key' => 'hbp_ABC',
            'events' => [
                'bulk_threshold' => 2,
                'dispatch_interval_seconds' => 2,
            ]
        ]);
        $events = [
            ['event_type' => 'log', 'ts' => (new DateTime())->format(DATE_RFC3339_EXTENDED), 'message' => 'test 1'],
            ['event_type' => 'log', 'ts' => (new DateTime())->format(DATE_RFC3339_EXTENDED), 'message' => 'test 2'],
        ];
        $hbMock = $this->createMock(HoneybadgerClient::class);
        $hbMock->expects($this->once())->method('events')->with($events);
        $dispatcher = new BulkEventDispatcher($config, $hbMock);
        $dispatcher->addEvent($events[0]);
        $this->assertTrue($dispatcher->hasEvents());
        $dispatcher->addEvent($events[1]);
        $this->assertFalse($dispatcher->hasEvents());
    }

    /** @test */
    public function it_sends_events_when_interval_is_reached() {
        $config = new Config([
            'api_key' => 'hbp_ABC',
            'events' => [
                'bulk_threshold' => 50,
                'dispatch_interval_seconds' => 2,
            ]
        ]);
        $events = [
            ['event_type' => 'log', 'ts' => (new DateTime())->format(DATE_RFC3339_EXTENDED), 'message' => 'test 1'],
            ['event_type' => 'log', 'ts' => (new DateTime())->format(DATE_RFC3339_EXTENDED), 'message' => 'test 2'],
        ];
        $hbMock = $this->createMock(HoneybadgerClient::class);
        $hbMock->expects($this->once())->method('events')->with($events);
        $dispatcher = new BulkEventDispatcher($config, $hbMock);
        $dispatcher->addEvent($events[0]);
        $this->assertTrue($dispatcher->hasEvents());
        sleep(2);
        $dispatcher->addEvent($events[1]);
        $this->assertFalse($dispatcher->hasEvents());
    }

    /** @test */
    public function it_flushes_events() {
        $config = new Config([
            'api_key' => 'hbp_ABC',
            'events' => [
                'bulk_threshold' => 50,
                'dispatch_interval_seconds' => 2,
            ]
        ]);
        $events = [
            ['event_type' => 'log', 'ts' => (new DateTime())->format(DATE_RFC3339_EXTENDED), 'message' => 'test 1'],
            ['event_type' => 'log', 'ts' => (new DateTime())->format(DATE_RFC3339_EXTENDED), 'message' => 'test 2'],
        ];
        $hbMock = $this->createMock(HoneybadgerClient::class);
        $hbMock->expects($this->once())->method('events')->with($events);
        $dispatcher = new BulkEventDispatcher($config, $hbMock);
        $dispatcher->addEvent($events[0]);
        $dispatcher->addEvent($events[1]);
        $this->assertTrue($dispatcher->hasEvents());
        $dispatcher->flushEvents();
        $this->assertFalse($dispatcher->hasEvents());
    }

}
