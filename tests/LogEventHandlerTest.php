<?php

namespace Honeybadger\Tests;

use Honeybadger\BulkEventDispatcher;
use Honeybadger\Config;
use Honeybadger\Contracts\Reporter;
use Honeybadger\Honeybadger;
use Honeybadger\HoneybadgerClient;
use Honeybadger\LogEventHandler;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class LogEventHandlerTest extends TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $reporter = $this->createMock(Reporter::class);

        $this->assertInstanceOf(
            AbstractProcessingHandler::class,
            new LogEventHandler($reporter)
        );
    }

    /** @test */
    public function it_formats_a_log_for_events_api()
    {
        $client = $this->createMock(HoneybadgerClient::class);
        $config = new Config([
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $reporter = new Honeybadger($config->all(), null, $eventsDispatcher);
        $logger = new Logger('test-logger');
        $logger->pushHandler(new LogEventHandler($reporter));

        $logger->info('Test log message', ['some' => 'data']);

        $this->assertEquals([[
            'event_type' => 'log',
            'ts' => (new \DateTime())->format(DATE_ATOM),
            'channel' => 'test-logger',
            'message' => 'Test log message',
            'severity' => 'info',
            'some' => 'data',
        ]], $eventsDispatcher->events);
    }

    /** @test */
    public function it_ignores_logs_below_its_minimum_level()
    {
        $client = $this->createMock(HoneybadgerClient::class);
        $config = new Config([
            'events' => [
                'enabled' => true
            ]
        ]);
        $eventsDispatcher = new class($config, $client) extends BulkEventDispatcher {
            public $events = [];

            public function __construct(Config $config, HoneybadgerClient $client)
            {
                parent::__construct($config, $client);
            }

            public function addEvent($event): void
            {
                $this->events[] = $event;
            }
        };
        $reporter = new Honeybadger($config->all(), null, $eventsDispatcher);
        $logger = new Logger('test-logger');
        $logger->pushHandler(new LogEventHandler($reporter, Logger::INFO));

        $logger->debug('Test debug message', ['some' => 'data']);
        $logger->warning('Test warning message', ['some' => 'data']);

        $this->assertEquals([[
            'event_type' => 'log',
            'ts' => (new \DateTime())->format(DATE_ATOM),
            'channel' => 'test-logger',
            'message' => 'Test warning message',
            'severity' => 'warning',
            'some' => 'data',
        ]], $eventsDispatcher->events);
    }
}
