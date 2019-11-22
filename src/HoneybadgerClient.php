<?php

namespace Honeybadger;

use Exception;
use GuzzleHttp\Client;
use Honeybadger\Exceptions\ServiceException;
use Honeybadger\Exceptions\ServiceExceptionFactory;
use Symfony\Component\HttpFoundation\Response;

class HoneybadgerClient
{
    /**
     * @var \Honeybadger\Config
     */
    protected $config;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @param  \Honeybadger\Config  $config
     * @param  \GuzzleHttp\Client  $client
     */
    public function __construct(Config $config, Client $client = null)
    {
        $this->config = $config;
        $this->client = $client ?? $this->makeClient();
    }

    /**
     * @param  array  $notification
     * @return array
     *
     * @throws \Honeybadger\Exceptions\ServiceException
     */
    public function notification(array $notification) : array
    {
        try {
            $response = $this->client->post(
                'notices',
                ['body' => json_encode($notification)]
            );
        } catch (Exception $e) {
            throw ServiceException::generic();
        }

        if ($response->getStatusCode() !== Response::HTTP_CREATED) {
            throw (new ServiceExceptionFactory($response))->make();
        }

        return (string) $response->getBody()
            ? json_decode($response->getBody(), true)
            : [];
    }

    /**
     * @param  string  $key
     * @return void
     *
     * @throws \Honeybadger\Exceptions\ServiceException
     */
    public function checkin(string $key) : void
    {
        try {
            $response = $this->client->head(sprintf('check_in/%s', $key));
        } catch (Exception $e) {
            throw ServiceException::generic();
        }

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw (new ServiceExceptionFactory($response))->make();
        }
    }

    /**
     * @return \GuzzleHttp\Client
     */
    private function makeClient() : Client
    {
        return new Client([
            'base_uri' => Honeybadger::API_URL,
            'http_errors' => false,
            'headers' => [
                'X-API-Key' => $this->config['api_key'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'timeout' => $this->config['client']['timeout'],
            'proxy' => $this->config['client']['proxy'],
        ]);
    }
}
