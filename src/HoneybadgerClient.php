<?php

namespace Honeybadger;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
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

    /**
     * @param  string  $checkInId
     * @return void
     */
    public function checkIn(string $checkInId): void
    {
        try {
            $response = $this->client->head(sprintf('v1/check_in/%s', $checkInId));

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                $this->handleServiceException((new ServiceExceptionFactory($response))->make());
            }
        } catch (Throwable $e) {
            $this->handleServiceException(ServiceException::generic($e));
        }
    }

    public function checkInWithSlug(string $apiKey, string $checkInSlug): void
    {
        try {
            $response = $this->client->head(sprintf('v1/check_in/%s/%s', $apiKey, $checkInSlug));

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                $this->handleServiceException((new ServiceExceptionFactory($response))->make());
            }
        } catch (Throwable $e) {
            $this->handleServiceException(ServiceException::generic($e));
        }
    }

    /**
     * @param array $events
     * @return void
     */
    public function events(array $events): void
    {
        try {
            $ndjson = implode("\n", array_map('json_encode', $events));
            $response = $this->client->post(
                'v1/events',
                ['body' => $ndjson]
            );
        } catch (Throwable $e) {
            $this->handleEventsException(ServiceException::generic($e));

            return;
        }

        if ($response->getStatusCode() !== Response::HTTP_CREATED) {
            $this->handleEventsException((new ServiceExceptionFactory($response))->make());
        }
    }

    public function makeClient(): Client
    {

        return new Client([
            'base_uri' => $this->config['endpoint'],
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::HEADERS => [
                'X-API-Key' => $this->config['api_key'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => $this->getUserAgent(),
            ],
            RequestOptions::TIMEOUT => $this->config['client']['timeout'],
            RequestOptions::PROXY => $this->config['client']['proxy'],
            RequestOptions::VERIFY => $this->config['client']['verify'] ?? true,
        ]);
    }


}
