<?php

namespace Honeybadger;

use Guzzle\Http\Client;

/**
 * @package  Honeybadger
 */
class Sender
{

    /**
     * Endpoint URL prefix
     */
    const NOTICES_URI = '/v1/notices/';
    /**
     * @var array
     */
    protected static $default_headers = [
        'Accept'       => 'application/json',
        'Content-Type' => 'application/json; charset=utf-8',
    ];

    /**
     * @param $notice
     *
     * @return mixed
     * @throws \Exception
     */
    public function sendToHoneybadger($notice)
    {
        if ($notice instanceof Notice) {
            $data = $notice->toJson();
        } else {
            $data = (string)$notice;
        }

        $headers = [];
        if ($api_key = Honeybadger::$config->api_key) {
            $headers['X-API-Key'] = $api_key;
        }

        $response = $this->setupHttpClient()
                         ->post(self::NOTICES_URI, $headers, $data)
                         ->send();

        $body = $response->json();

        return $body['id'];
    }

    /**
     * @return Client
     * @throws \Exception
     */
    private function setupHttpClient()
    {
        // Fetch a copy of the configuration.
        $config = Honeybadger::$config;

        $options = [
            'curl.options' => [
                // Timeouts
                'CURLOPT_CONNECTTIMEOUT' => $config->http_open_timeout,
                'CURLOPT_TIMEOUT'        => $config->http_read_timeout,
                // Location redirects
                'CURLOPT_AUTOREFERER'    => true,
                'CURLOPT_FOLLOWLOCATION' => true,
                'CURLOPT_MAXREDIRS'      => 10,
            ],
        ];

        if ($config->proxy_host) {
            $options['curl.options']['CURLOPT_HTTPPROXYTUNNEL'] = true;
            $options['curl.options']['CURLOPT_PROXY']           = $config->proxy_host;
            $options['curl.options']['CURLOPT_PROXYPORT']       =
                $config->proxy_user . ':' . $config->proxy_pass;
        }

        if ($config->isSecure()) {
            $options['ssl.certificate_authority'] = $config->certificate_authority;
        }

        try {
            $client = new Client($config->baseUrl(), $options);
            $client->setDefaultHeaders(self::$default_headers);
            $client->setUserAgent($this->userAgent());

            return $client;
        }
        catch (\Exception $e) {
            // $this->log(Logger::ERROR,
            // '['.__CLASS__.'::setup_http_client] Failure initializing the request client. Error: [ '.$e->getCode().' ] '.$e->getMessage());

            // Rethrow the exception
            throw $e;
        }
    }

    /**
     * @return string
     */
    private function userAgent()
    {
        return sprintf(
            '%s v%s (%s)',
            Honeybadger::NOTIFIER_NAME,
            Honeybadger::VERSION,
            Honeybadger::NOTIFIER_URL
        );
    }
} // End Sender
