<?php

namespace Honeybadger;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Honeybadger\Contracts\ApiClient;
use Honeybadger\Exceptions\ServiceException;
use Honeybadger\Exceptions\ServiceExceptionFactory;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckInsClient extends ApiClient
{
    const BASE_URL = 'https://app.honeybadger.io/';

    /**
     * @param string $projectApiKey
     * @return string The project ID for the given API key.
     *
     * @throws ServiceException
     */
    public function getProjectId(string $projectApiKey): string
    {
        if (! $this->hasPersonalAuthToken()) {
            throw ServiceException::missingPersonalAuthToken();
        }

        try {
            $url = sprintf('v2/project_keys/%s', $projectApiKey);
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw (new ServiceExceptionFactory($response))->make();
            }

            $data = json_decode($response->getBody(), true);

            return $data['project']['id'];
        } catch (ServiceException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ServiceException::generic($e);
        }
    }

    /**
     * @param string $projectId
     * @return CheckIn[]|null
     *
     * @throws ServiceException
     */
    public function listForProject(string $projectId): ?array
    {
        if (! $this->hasPersonalAuthToken()) {
            throw ServiceException::missingPersonalAuthToken();
        }

        try {
            $url = sprintf('v2/projects/%s/check_ins', $projectId);
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw (new ServiceExceptionFactory($response))->make();
            }

            $data = json_decode($response->getBody(), true);

            return array_map(function ($checkIn) use ($projectId) {
                return new CheckIn($checkIn);
            }, $data['results']);
        } catch (ServiceException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ServiceException::generic($e);
        }
    }

    /**
     * @throws ServiceException
     */
    public function get(string $projectId, string $checkinId): CheckIn
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

            return new CheckIn($data);
        } catch (ServiceException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ServiceException::generic($e);
        }
    }

    /**
     * @throws ServiceException
     */
    public function create(string $projectId, CheckIn $checkIn): CheckIn
    {
        if (! $this->hasPersonalAuthToken()) {
            throw ServiceException::missingPersonalAuthToken();
        }

        try {
            $url = sprintf('v2/projects/%s/check_ins', $projectId);
            $response = $this->client->post($url, [
                'json' => [
                    'check_in' => $checkIn->asRequestData()
                ]
            ]);

            if ($response->getStatusCode() !== Response::HTTP_CREATED) {
                throw (new ServiceExceptionFactory($response))->make();
            }

            $data = json_decode($response->getBody(), true);

            return new CheckIn($data);
        } catch (ServiceException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ServiceException::generic($e);
        }
    }

    /**
     * @throws ServiceException
     */
    public function update(string $projectId, CheckIn $checkIn): CheckIn
    {
        if (! $this->hasPersonalAuthToken()) {
            throw ServiceException::missingPersonalAuthToken();
        }

        try {
            $url = sprintf('v2/projects/%s/check_ins/%s', $projectId, $checkIn->id);
            $response = $this->client->put($url, [
                'json' => [
                    'check_in' => $checkIn->asRequestData()
                ]
            ]);

            if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
                throw (new ServiceExceptionFactory($response))->make();
            }

            return $checkIn;
        } catch (ServiceException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ServiceException::generic($e);
        }
    }

    /**
     * @throws ServiceException
     */
    public function remove(string $projectId, string $checkInId): void {
        if (! $this->hasPersonalAuthToken()) {
            throw ServiceException::missingPersonalAuthToken();
        }

        try {
            $url = sprintf('v2/projects/%s/check_ins/%s', $projectId, $checkInId);
            $response = $this->client->delete($url);

            if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
                throw (new ServiceExceptionFactory($response))->make();
            }
        } catch (ServiceException $e) {
            throw $e;
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
