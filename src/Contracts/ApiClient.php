<?php

namespace Honeybadger\Contracts;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Honeybadger\Config;
use Honeybadger\Exceptions\ServiceException;

abstract class ApiClient {

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param Config $config
     * @param Client|null $httpClient
     */
    public function __construct(Config $config, Client $httpClient = null) {
        $this->config = $config;
        $this->client = $httpClient ?? $this->makeClient();
    }

    protected function handleServiceException(ServiceException $e): void
    {
        $serviceExceptionHandler = $this->config['service_exception_handler'];
        call_user_func_array($serviceExceptionHandler, [$e]);
    }

    /**
     * @return Client
     */
    protected function makeClient(): Client
    {
        return new Client([
            'base_uri' => $this->config['endpoint'],
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::AUTH => [
                $this->config['personal_auth_token'], ''
            ],
            RequestOptions::HEADERS => [
                'X-API-Key' => $this->config['api_key'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            RequestOptions::TIMEOUT => $this->config['client']['timeout'],
            RequestOptions::PROXY => $this->config['client']['proxy'],
            RequestOptions::VERIFY => $this->config['client']['verify'] ?? true,
        ]);
    }

    public function hasPersonalAuthToken(): bool
    {
        return !empty($this->config['personal_auth_token']);
    }

}
