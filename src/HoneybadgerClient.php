<?php

namespace Honeybadger;

use Honeybadger\Contracts\ApiClient;
use Honeybadger\Exceptions\ServiceException;
use Honeybadger\Exceptions\ServiceExceptionFactory;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HoneybadgerClient extends ApiClient
{
    /**
     * @param  array  $notification
     * @return array
     */
    public function notification(array $notification): array
    {
        try {
            $response = $this->client->post(
                'v1/notices',
                ['body' => json_encode($notification, JSON_PARTIAL_OUTPUT_ON_ERROR)]
            );
        } catch (Throwable $e) {
            $this->handleServiceException(ServiceException::generic($e));

            return [];
        }

        if ($response->getStatusCode() !== Response::HTTP_CREATED) {
            $this->handleServiceException((new ServiceExceptionFactory($response))->make());

            return [];
        }

        return (string) $response->getBody()
            ? json_decode($response->getBody(), true)
            : [];
    }
}
