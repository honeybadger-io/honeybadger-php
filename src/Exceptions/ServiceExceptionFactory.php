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

    public function make(): ServiceException
    {
        return $this->exception();
    }

    private function exception(): ServiceException
    {
        if ($this->response->getStatusCode() === Response::HTTP_FORBIDDEN) {
            return ServiceException::invalidApiKey();
        }

        if ($this->response->getStatusCode() === Response::HTTP_UNPROCESSABLE_ENTITY) {
            return ServiceException::invalidPayload();
        }

        if ($this->response->getStatusCode() === Response::HTTP_TOO_MANY_REQUESTS) {
            return ServiceException::rateLimit();
        }

        if ($this->response->getStatusCode() === Response::HTTP_INTERNAL_SERVER_ERROR) {
            return ServiceException::serverError();
        }

        return ServiceException::unexpectedResponseCode($this->response->getStatusCode());
    }
}
