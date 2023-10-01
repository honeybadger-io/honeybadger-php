<?php

namespace Honeybadger\Tests;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Honeybadger\Checkin;
use Honeybadger\CheckinsClient;
use Honeybadger\Config;
use Honeybadger\Exceptions\ServiceException;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CheckinsClientTest extends TestCase
{
    /** @test */
    public function throws_generic_exception_for_checkins()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('There was an error sending the payload to Honeybadger');

        $config = new Config([
            'personal_auth_token' => '5678'
        ]);
        $mock = Mockery::mock(Client::class)->makePartial();
        $mock->shouldReceive('get')->andThrow(new Exception);

        $client = new CheckinsClient($config, $mock);
        $client->get('p1234', 'c1234');
    }

    /** @test */
    public function allows_service_exceptions_to_be_handled()
    {
        $message = null;
        $config = new Config([
            'personal_auth_token' => '5678',
            'service_exception_handler' => function (ServiceException $e) use (&$message) {
                $message = $e->getMessage();
            },
        ]);
        $mock = Mockery::mock(Client::class)->makePartial();
        $mock->shouldReceive('get')->andReturn(new GuzzleResponse(Response::HTTP_INTERNAL_SERVER_ERROR));

        $client = new CheckinsClient($config, $mock);
        $client->get('p1234', 'c1234');

        $this->assertStringContainsString('There was an error on our end.', $message);
    }

    /** @test */
    public function throws_exception_when_personal_auth_token_is_missing()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Missing personal auth token. This token is required to use Honeybadger's Data APIs.");

        $config = new Config([]);
        $mock = Mockery::mock(Client::class);

        $client = new CheckinsClient($config, $mock);
        $client->get('p1234', 'c1234');
    }

    /** @test */
    public function creates_checkin_and_populates_id()
    {
        $config = new Config([
            'personal_auth_token' => '5678'
        ]);
        $mock = Mockery::mock(Client::class)->makePartial();
        $mock->shouldReceive('post')
            ->andReturn(new GuzzleResponse(Response::HTTP_CREATED, [], json_encode(['id' => '1234'])));

        $client = new CheckinsClient($config, $mock);
        $checkin = $client->create(new Checkin());

        $this->assertEquals('1234', $checkin->id);
    }
}
