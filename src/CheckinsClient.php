<?php

namespace Honeybadger;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Honeybadger\Contracts\ApiClient;
use Honeybadger\Exceptions\ServiceException;
use Honeybadger\Exceptions\ServiceExceptionFactory;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckinsClient extends ApiClient
{
    const BASE_URL = 'https://app.honeybadger.io/';

    /**
     * @var Checkin[][]
     */
    private $projectCheckins = [];

    /**
     * @param string $projectId
     * @return Checkin[]|null
     *
     * @throws ServiceException
     */
    public function listForProject(string $projectId): ?array
    {
        if (! $this->hasPersonalAuthToken()) {
            throw ServiceException::missingPersonalAuthToken();
        }

        if (isset($this->projectCheckins[$projectId])) {
            return $this->projectCheckins[$projectId];
        }

        try {
            $url = sprintf('v2/projects/%s/check_ins', $projectId);
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw (new ServiceExceptionFactory($response))->make();
            }

            $data = json_decode($response->getBody(), true);
            $this->projectCheckins[$projectId] = array_map(function ($checkin) use ($projectId) {
                $result = new Checkin($checkin);
                $result->projectId = $projectId;

                return $result;
            }, $data['results']);
            return $this->projectCheckins[$projectId];
        } catch (Throwable $e) {
            throw ServiceException::generic($e);
        }
    }

    /**
     * @throws ServiceException
     */
    public function get(string $projectId, string $checkinId): Checkin
    {
        if (! $this->hasPersonalAuthToken()) {
            throw ServiceException::missingPersonalAuthToken();
        }

        try {
            $url = sprintf('v2/projects/%s/check_ins/%s', $projectId, $checkinId);
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw (new ServiceExceptionFactory($response))->make();
            }

            $data = json_decode($response->getBody(), true);
            return new Checkin($data);
        } catch (Throwable $e) {
            throw ServiceException::generic($e);
        }
    }

    /**
     * @throws ServiceException
     */
    public function create(Checkin $checkin): Checkin
    {
        if (! $this->hasPersonalAuthToken()) {
            throw ServiceException::missingPersonalAuthToken();
        }

        try {
            $url = sprintf('v2/projects/%s/check_ins', $checkin->projectId);
            $response = $this->client->post($url, [
                'json' => [
                    'check_in' => $checkin->asRequestData()
                ]
            ]);

            if ($response->getStatusCode() !== Response::HTTP_CREATED) {
                throw (new ServiceExceptionFactory($response))->make();
            }

            $data = json_decode($response->getBody(), true);
            return new Checkin($data);
        } catch (Throwable $e) {
            throw ServiceException::generic($e);
        }
    }

    /**
     * @throws ServiceException
     */
    public function update(Checkin $checkin): Checkin
    {
        if (! $this->hasPersonalAuthToken()) {
            throw ServiceException::missingPersonalAuthToken();
        }

        try {
            $url = sprintf('v2/projects/%s/check_ins/%s', $checkin->projectId, $checkin->id);
            $response = $this->client->put($url, [
                'json' => [
                    'check_in' => $checkin->asRequestData()
                ]
            ]);

            if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
                throw (new ServiceExceptionFactory($response))->make();
            }

            return $checkin;
        } catch (Throwable $e) {
            throw ServiceException::generic($e);
        }
    }

    /**
     * @throws ServiceException
     */
    public function remove(string $projectId, string $checkinId): void {
        if (! $this->hasPersonalAuthToken()) {
            throw ServiceException::missingPersonalAuthToken();
        }

        try {
            $url = sprintf('v2/projects/%s/check_ins/%s', $projectId, $checkinId);
            $response = $this->client->delete($url);

            if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
                throw (new ServiceExceptionFactory($response))->make();
            }
        } catch (Throwable $e) {
            throw ServiceException::generic($e);
        }
    }

    public function makeClient(): Client
    {
        return new Client([
            'base_uri' => self::BASE_URL,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::AUTH => [
                $this->config['personal_auth_token'], ''
            ],
        ]);
    }
}
