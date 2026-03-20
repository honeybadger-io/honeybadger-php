<?php

namespace Honeybadger\Tests;

use Mockery;
use Exception;
use GuzzleHttp\Client;
use Honeybadger\Config;
use Honeybadger\CheckInsClient;
use PHPUnit\Framework\TestCase;
use Honeybadger\Exceptions\ServiceException;
use Honeybadger\CheckInsClientWithErrorHandling;

class CheckInsClientWithErrorHandlingTest extends TestCase
{
    /** @test */
    public function allows_service_exceptions_to_be_handled()
    {
        $message = null;
        $config = new Config([
            'api_key' => '1234',
            'personal_auth_token' => '1234',
            'service_exception_handler' => function (ServiceException $e) use (&$message) {
                $message = $e->getMessage();
            },
        ]);
        $mock = Mockery::mock(Client::class)->makePartial();
        $mock->shouldReceive('get')->andThrow(new Exception());

        /** @var CheckInsClient $client */
        $client = new CheckInsClientWithErrorHandling($config, $mock);
        $client->get('p1234', 'c1234');

        $this->assertStringContainsString('There was an error sending the payload to Honeybadger', $message);
    }
}
