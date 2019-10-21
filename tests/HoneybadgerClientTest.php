<?php

namespace Honeybadger\Tests;

use Mockery;
use Exception;
use GuzzleHttp\Client;
use Honeybadger\Config;
use PHPUnit\Framework\TestCase;
use Honeybadger\HoneybadgerClient;
use Honeybadger\Exceptions\ServiceException;
use Symfony\Component\HttpFoundation\Response;

class HoneybadgerClientTest extends TestCase
{
    /** @test */
    public function throws_generic_exception_for_notifications()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('There was an error sending the payload to Honeybadger.');

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
        $this->expectExceptionMessage('There was an error sending the payload to Honeybadger.');

        $config = new Config(['api_key' => '1234']);
        $mock = Mockery::mock(Client::class)->makePartial();
        $mock->shouldReceive('head')->andThrow(new Exception);

        $client = new HoneybadgerClient($config, $mock);
        $client->checkin('1234');
    }

    /** @test */
    public function doesnt_throw_when_passing_recursive_data()
    {
        $data = [];
        $data['data'] = &$data;

        $config = new Config(['api_key' => '1234']);

        $responseMock = Mockery::mock(Response::class)
            ->shouldReceive([
                'getStatusCode' => Response::HTTP_CREATED,
                'getBody' => '',
            ])
            ->getMock();

        $clientMock = Mockery::mock(Client::class);
        $clientMock->shouldReceive('post')
            ->with('notices', ['body' => '{"data":null}'])
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
