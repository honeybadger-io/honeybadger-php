<?php

namespace Honeybadger\Contracts;

use GuzzleHttp\Client;
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

    /**
     * @return Client
     */
    public abstract function makeClient(): Client;

    protected function handleServiceException(ServiceException $e): void
    {
        $serviceExceptionHandler = $this->config['service_exception_handler'];
        call_user_func_array($serviceExceptionHandler, [$e]);
    }

    public function hasPersonalAuthToken(): bool
    {
        return !empty($this->config['personal_auth_token']);
    }

}
