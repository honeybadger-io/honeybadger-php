<?php

namespace Honeybadger;

use Honeybadger\Sender;
use Honeybadger\GuzzleFactory;
use Mockery;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;

/**
 * Tests Honeybadger\Sender.
 *
 * @group honeybadger
 */
class SenderTest extends \PHPUnit\Framework\TestCase
{
  public function test_proxy_should_only_include_user_if_set()
  {
      Honeybadger::$config->set('api_key', 'asdf-123');
      Honeybadger::$config->set('proxy_host', 'localhost');
      Honeybadger::$config->set('proxy_port', '8080');

      $client = new Client([
        'handler' => new MockHandler([new Response(201, [], json_encode(['id' => 1]))])
      ]);

      $mock = Mockery::mock(GuzzleFactory::class)
        ->shouldReceive('make')
        ->once()
        ->with(Mockery::subset(['proxy' => 'tcp://localhost:8080']))
        ->andReturn($client)
        ->getMock();

        $notice = 'asdf';

      (new Sender($mock))->sendToHoneybadger($notice);
  }
}
