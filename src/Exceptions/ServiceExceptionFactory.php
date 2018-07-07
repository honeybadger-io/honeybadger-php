<?php

namespace Honeybadger\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class ServiceExceptionFactory
{
    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * @param  \Psr\Http\Message\ResponseInterface  $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function make() : Exception
    {
        return $this->exception();
    }

    /**
     * @return void
     *
     * @throws \Honeybadger\Exceptions\ServiceException
     */
    private function exception() : void
    {
        if ($this->response->getStatusCode() === Response::HTTP_FORBIDDEN) {
            throw ServiceException::invalidApiKey();
        }

        if ($this->response->getStatusCode() === Response::HTTP_UNPROCESSABLE_ENTITY) {
            throw ServiceException::invalidPayload();
        }

        if ($this->response->getStatusCode() === Response::HTTP_TOO_MANY_REQUESTS) {
            throw ServiceException::rateLimit();
        }

        if ($this->response->getStatusCode() === Response::HTTP_INTERNAL_SERVER_ERROR) {
            throw ServiceException::serverError();
        }

        throw ServiceException::generic();
    }
}
