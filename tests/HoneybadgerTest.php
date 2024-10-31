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
    // ... (existing tests)

    /** @test */
    public function it_handles_empty_api_key_correctly()
    {
        $badger = Honeybadger::new(['api_key' => '']);
        $this->assertEmpty($badger->notify(new Exception('Test exception')));
    }

    /** @test */
    public function it_respects_report_data_config()
    {
        $badger = Honeybadger::new([
            'api_key' => 'test_key',
            'report_data' => false
        ]);
        $this->assertEmpty($badger->notify(new Exception('Test exception')));
    }

    // ... (rest of the existing tests)
}