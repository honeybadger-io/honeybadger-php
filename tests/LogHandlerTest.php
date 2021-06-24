<?php

namespace Honeybadger\Tests;

use DateTime;
use Honeybadger\Contracts\Reporter;
use Honeybadger\Honeybadger;
use Honeybadger\LogHandler;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class LogHandlerTest extends TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $reporter = $this->createMock(Reporter::class);

        $this->assertInstanceOf(
            AbstractProcessingHandler::class,
            new LogHandler($reporter)
        );
    }

    /** @test */
    public function it_will_format_a_log_for_honeybadger()
    {
        $reporter = new class extends Honeybadger {
            public $notification;

            public function __construct()
            {
                parent::__construct();
            }

            public function rawNotification(callable $fn): array
            {
                $this->notification = $fn($this->config);

                return [];
            }
        };

        $logger = new Logger('test-logger');
        $logger->pushHandler(new LogHandler($reporter));

        $logger->info('Test log message', ['some' => 'data']);

        $this->assertEquals([
            'notifier' => [
                'name' => 'Honeybadger Log Handler',
                'url' => 'https://github.com/honeybadger-io/honeybadger-php',
                'version' => Honeybadger::VERSION,
            ],
            'request' => [
                'context' => [
                    'level_name' => 'INFO',
                    'log_channel' => 'test-logger',
                    'some' => 'data',
                ],
            ],
        ], array_only($reporter->notification, ['notifier', 'request',]));

        $this->assertArrayHasKey('time', $reporter->notification['server']);
        $this->assertEquals('production', $reporter->notification['server']['environment_name']);

        $this->assertEquals([
            'class' => 'INFO Log',
            'message' => 'Test log message',
            'tags' => [
                'log',
                'test-logger.INFO',
            ],
        ], array_only($reporter->notification['error'], ['class', 'tags', 'message']));

        $this->assertFalse(empty($reporter->notification['error']['fingerprint']));
    }

    /** @test */
    public function formats_exception_logs_properly()
    {
        $reporter = new class extends Honeybadger {
            public $notification;

            public function __construct()
            {
                parent::__construct();
            }

            public function rawNotification(callable $fn): array
            {
                $this->notification = $fn($this->config);

                return [];
            }
        };

        $logger = new Logger('test-logger');
        $logger->pushHandler(new LogHandler($reporter));

        $e = new \InvalidArgumentException('Baa baa');
        $logger->error('Test log message', ['exception' => $e]);

        $this->assertEquals([
            'notifier' => [
                'name' => 'Honeybadger Log Handler',
                'url' => 'https://github.com/honeybadger-io/honeybadger-php',
                'version' => Honeybadger::VERSION,
            ],
            'request' => [
                'context' => [
                    'level_name' => 'ERROR',
                    'log_channel' => 'test-logger',
                    'exception' => [
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTrace(),
                    ],
                ],
            ],
        ], array_only($reporter->notification, ['notifier', 'request',]));

        $this->assertArrayHasKey('time', $reporter->notification['server']);
        $this->assertEquals('production', $reporter->notification['server']['environment_name']);

        $this->assertEquals([
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'tags' => [
                'log',
                'test-logger.ERROR',
            ],
        ], array_only($reporter->notification['error'], ['class', 'tags', 'message']));

        $this->assertFalse(empty($reporter->notification['error']['fingerprint']));
    }
}
