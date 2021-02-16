<?php

namespace Honeybadger\Tests;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Honeybadger\Exceptions\ServiceException;
use Honeybadger\Exceptions\ServiceExceptionFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ServiceExceptionTest extends TestCase
{
    /** @test */
    public function it_throws_an_exception_if_the_api_key_is_bad()
    {
        $this->expectExceptionObject(ServiceException::invalidApiKey());

        $response = new GuzzleResponse(Response::HTTP_FORBIDDEN);

        throw (new ServiceExceptionFactory($response))->make();
    }

    /** @test */
    public function it_throws_an_exception_if_validation_fails()
    {
        $this->expectExceptionObject(ServiceException::invalidPayload());

        $response = new GuzzleResponse(Response::HTTP_UNPROCESSABLE_ENTITY);

        throw (new ServiceExceptionFactory($response))->make();
    }

    /** @test */
    public function it_throws_an_exception_if_it_hits_a_rate_limit()
    {
        $this->expectExceptionObject(ServiceException::rateLimit());

        $response = new GuzzleResponse(Response::HTTP_TOO_MANY_REQUESTS);

        throw (new ServiceExceptionFactory($response))->make();
    }

    /** @test */
    public function it_throws_an_exception_if_there_is_a_server_error()
    {
        $this->expectExceptionObject(ServiceException::serverError());

        $response = new GuzzleResponse(Response::HTTP_INTERNAL_SERVER_ERROR);

        throw (new ServiceExceptionFactory($response))->make();
    }

    /** @test */
    public function it_throws_a_generic_exception_if_all_else_fails()
    {
        $this->expectExceptionObject(ServiceException::generic());

        $response = new GuzzleResponse(Response::HTTP_I_AM_A_TEAPOT);

        throw (new ServiceExceptionFactory($response))->make();
    }
}
