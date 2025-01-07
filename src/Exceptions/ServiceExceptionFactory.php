<?php

namespace Honeybadger\Exceptions;

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

    public function make(bool $isFromEventsApi = false): ServiceException
    {
        return $this->exception($isFromEventsApi);
    }

    private function exception(bool $isFromEventsApi = false): ServiceException
    {
        $message = $this->response->getBody()->getContents();
        if (!empty($message)) {
            $data = json_decode($message, true);
            if (isset($data['errors'])) {
                return ServiceException::withMessage($data['errors']);
            }
        }

        if ($this->response->getStatusCode() === Response::HTTP_FORBIDDEN) {
            return ServiceException::invalidApiKey();
        }

        if ($this->response->getStatusCode() === Response::HTTP_UNPROCESSABLE_ENTITY) {
            return ServiceException::invalidPayload();
        }

        if ($this->response->getStatusCode() === Response::HTTP_TOO_MANY_REQUESTS) {
            return $isFromEventsApi
                ? ServiceException::eventsRateLimit()
                : ServiceException::rateLimit();
        }

        if ($this->response->getStatusCode() === Response::HTTP_INTERNAL_SERVER_ERROR) {
            return ServiceException::serverError();
        }

        return ServiceException::unexpectedResponseCode($this->response->getStatusCode());
    }
}
