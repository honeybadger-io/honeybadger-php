<?php

namespace Honeybadger\Tests;

use Monolog\Logger;
use Honeybadger\LogHandler;
use Honeybadger\Honeybadger;
use PHPUnit\Framework\TestCase;
use Honeybadger\Contracts\Reporter;
use Monolog\Handler\AbstractProcessingHandler;

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

            public function rawNotification(callable $fn) : array
            {
                $this->notification = $fn($this->config);

                return [];
            }
        };

        $logger = new Logger('test-logger');
        $logger->pushHandler(new LogHandler($reporter));

        $logger->info('Test log message');

        $this->assertEquals([
            'notifier' => [
                'name' => 'Honeybadger Log Handler',
                'url' => 'https://github.com/honeybadger-io/honeybadger-php',
                'version' => Honeybadger::VERSION,
            ],
            'request' => [
                'context' => [
                    'context' => [],
                    'level_name' => 'INFO',
                    'log_channel' => 'test-logger',
                    'message' => 'Test log message',
                ],
            ],
          ], array_only($reporter->notification, ['notifier', 'request']));

        $this->assertEquals([
            'class' => 'Test log message',
            'tags' => [
                'log',
                'test-logger.INFO',
            ],
        ], array_only($reporter->notification['error'], ['class', 'tags']));

        // [2019-09-10T18:49:15.681206+00:00] test-logger.INFO: Test log message
        $this->assertRegExp(
            '/\[[0-9-:+T.\s]+\] test-logger\.INFO\: Test log message/',
            $reporter->notification['error']['message']
        );

        $this->assertFalse(empty($reporter->notification['error']['fingerprint']));
    }
}
