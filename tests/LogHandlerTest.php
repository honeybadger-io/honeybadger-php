<?php

namespace Honeybadger\Tests;

use Honeybadger\LogHandler;
use PHPUnit\Framework\TestCase;
use Honeybadger\Contracts\Reporter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Honeybadger\Honeybadger;

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

            public function __construct() {
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
        
        $this->assertArraySubset([
            'notifier' => [
                'name' => 'Honeybadger Log Handler',
                'url' => 'https://github.com/honeybadger-io/honeybadger-php',
                'version' => Honeybadger::VERSION,
            ],
            'error' => [
                'class' => 'Test log message',
                'tags' => [
                    'log',
                    'test-logger.INFO',
                ],
            ],
            'request' => [
                'context' => [
                    'context' => [],
                    'level_name' => 'INFO',
                    'log_channel' => 'test-logger',
                    'message' => 'Test log message',
                ]
            ]
          ], $reporter->notification);

        // [2019-01-20 14:56:20] test-logger.INFO: Test log message
        $this->assertRegExp(
           '/\[[0-9-:\s]+\] test-logger\.INFO\: Test log message/', 
            $reporter->notification['error']['message']
        );
        
        $this->assertFalse(empty($reporter->notification['error']['fingerprint']));
    }
}
