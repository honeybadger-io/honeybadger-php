<?php

namespace Honeybadger\Tests;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Honeybadger\CheckIn;
use Honeybadger\CheckInsClient;
use Honeybadger\Config;
use Honeybadger\Exceptions\ServiceException;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CheckInsClientTest extends TestCase
{
    /** @test */
    public function throws_generic_exception_for_check_ins()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('There was an error sending the payload to Honeybadger');

        $config = new Config([
            'personal_auth_token' => '5678'
        ]);
        $mock = Mockery::mock(Client::class)->makePartial();
        $mock->shouldReceive('get')->andThrow(new Exception);

        $client = new CheckInsClient($config, $mock);
        $client->get('p1234', 'c1234');
    }

    /** @test */
    public function throws_exception_when_personal_auth_token_is_missing()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Missing personal auth token. This token is required to use Honeybadger's Data APIs.");

        $config = new Config([]);
        $mock = Mockery::mock(Client::class);

        $client = new CheckInsClient($config, $mock);
        $client->get('p1234', 'c1234');
    }

    /** @test */
    public function gets_project_id_from_project_api_key()
    {
        $config = new Config([
            'api_key' => 'hbp_ABC',
            'personal_auth_token' => '5678'
        ]);
        $mock = Mockery::mock(Client::class)->makePartial();
        $mock->shouldReceive('get')
            ->andReturn(new GuzzleResponse(Response::HTTP_OK, [], json_encode(['project' => ['id' => '1234']])));

        $client = new CheckInsClient($config, $mock);
        $projectId = $client->getProjectId($config->get('api_key'));

        $this->assertEquals('1234', $projectId);
    }

    /** @test */
    public function creates_check_in_and_populates_id()
    {
        $config = new Config([
            'personal_auth_token' => '5678'
        ]);
        $mock = Mockery::mock(Client::class)->makePartial();
        $mock->shouldReceive('post')
            ->andReturn(new GuzzleResponse(Response::HTTP_CREATED, [], json_encode(['id' => '1234'])));

        $client = new CheckInsClient($config, $mock);
        $checkIn = $client->create('p1234', new CheckIn());

        $this->assertEquals('1234', $checkIn->id);
    }
}
