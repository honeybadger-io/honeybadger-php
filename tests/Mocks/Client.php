<?php

namespace Honeybadger\Tests\Mocks;

use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Honeybadger\Concerns\Newable;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Client as GuzzleClient;

class Client
{
    use Newable;

    protected $container = [];

    protected $responses;

    public function __construct($responses = null)
    {
        $this->responses = $responses ?? [
            new Response(200, ['X-Foo' => 'Bar']),
        ];
    }

    public function make($responses = null)
    {
        $stack = HandlerStack::create(new MockHandler($this->responses));

        $stack->push(Middleware::history($this->container));

        return new GuzzleClient([
            'handler' => $stack,
            'http_errors' => false,
        ]);
    }

    public function calls()
    {
        return $this->container;
    }
}
