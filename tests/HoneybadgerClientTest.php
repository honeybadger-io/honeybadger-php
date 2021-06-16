<?php

namespace Honeybadger\Tests;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Honeybadger\Config;
use Honeybadger\Exceptions\ServiceException;
use Honeybadger\HoneybadgerClient;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class HoneybadgerClientTest extends TestCase
{
    /** @test */
    public function throws_generic_exception_for_notifications()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('There was an error sending the payload to Honeybadger');

        $config = new Config(['api_key' => '1234']);
        $mock = Mockery::mock(Client::class)->makePartial();
        $mock->shouldReceive('post')->andThrow(new Exception);

        $client = new HoneybadgerClient($config, $mock);
        $client->notification([]);
    }

    /** @test */
    public function throws_generic_exception_for_checkins()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('There was an error sending the payload to Honeybadger');

        $config = new Config(['api_key' => '1234']);
        $mock = Mockery::mock(Client::class)->makePartial();
        $mock->shouldReceive('head')->andThrow(new Exception);

        $client = new HoneybadgerClient($config, $mock);
        $client->checkin('1234');
    }

    /** @test */
    public function allows_service_exceptions_to_be_handled()
    {
        $message = null;
        $config = new Config([
            'api_key' => '1234',
            'service_exception_handler' => function (ServiceException $e) use (&$message) {
                $message = $e->getMessage();
            },
        ]);
        $mock = Mockery::mock(Client::class)->makePartial();
        $mock->shouldReceive('post')->andThrow(new Exception);

        $client = new HoneybadgerClient($config, $mock);
        $client->notification([]);

        $this->assertStringContainsString('There was an error sending the payload to Honeybadger', $message);
    }

    /** @test */
    public function doesnt_throw_when_passing_recursive_data()
    {
        $data = [];
        $data['data'] = &$data;

        $config = new Config(['api_key' => '1234']);

        $responseMock = Mockery::mock(GuzzleResponse::class)
            ->shouldReceive([
                'getStatusCode' => Response::HTTP_CREATED,
                'getBody' => '',
            ])
            ->getMock();

        $clientMock = Mockery::mock(Client::class);
        $clientMock->shouldReceive('post')
            ->with('v1/notices', ['body' => '{"data":null}'])
            ->andReturn($responseMock);

        $client = new HoneybadgerClient($config, $clientMock);

        $assertionMessage = 'Unexpected result when passing recursive payload to `notification`';
        try {
            $result = $client->notification($data);
            $this->assertEquals([], $result, $assertionMessage);
        } catch (ServiceException $e) {
            $this->assertTrue(false, $assertionMessage);
        }
    }
}
