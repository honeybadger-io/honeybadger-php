<?php

namespace Honeybadger;

use GuzzleHttp\Client;

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
     * Headers for all requests
     *
     * @var array
     */
    protected static $default_headers = [
        'Accept'       => 'application/json',
        'Content-Type' => 'application/json; charset=utf-8'
    ];

    /**
     * @var \HoneyBadger\GuzzleFactory
     */
    protected $guzzleFactory;

    public function __construct($guzzleFactory)
    {
      $this->guzzleFactory = $guzzleFactory;
    }

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

        $headers = self::$default_headers;
        $options = ['exceptions' => false];

        $config  = Honeybadger::$config;
        $api_key = $config->api_key;

        if (!$api_key) {
            Honeybadger::$logger->critical(
                '[' . __CLASS__ . '::setup_http_client] Failure to send.
             Error: Missing API_KEY - required.'
            );

            return;
        }

        $headers['X-API-Key']  = $api_key;
        $headers['user-agent'] = $this->_userAgent();

        $options['base_uri'] = $config->baseUrl();
        $options['timeout']  = $config->http_open_timeout;

        // $options['debug']    = true;

        if ($config->proxy_host) {
            $options['proxy'] = 'tcp://';

            if ($config->proxy_user) {
                $options['proxy'] .= $config->proxy_user;
                if ($config->proxy_pass) {
                    $options['proxy'] .= ':' . $config->proxy_pass;
                }
                $options['proxy'] .= '@';
            }

            $options['proxy'] .= $config->proxy_host . ':' . $config->proxy_port;
        }

        if ($config->isSecure()) {
            $options['ssl.certificate_authority'] = $config->certificate_authority;
        }

        $client = $this->guzzleFactory->make($options);

        $response = $client->post(
            self::NOTICES_URI,
            ['headers' => $headers,
             'body'    => $data]
        );

        if ($response->getStatusCode() != 201) {
            Honeybadger::$logger->critical(
                '[' . __CLASS__ . '::http_client] Failure response. ' .
                $response->getStatusCode() .
                ' ' . $response->getReasonPhrase() .
                ' ' . $response->getBody()
            );

            return;
        }

        $body = (array)json_decode($response->getBody());

        return $body['id'];
    }

    /**
     * User agent for Honeybadger
     *
     * @return string
     */
    private function _userAgent()
    {
        return sprintf(
            '%s v%s (%s)',
            Honeybadger::NOTIFIER_NAME,
            Honeybadger::VERSION,
            Honeybadger::NOTIFIER_URL
        );
    }
} // End Sender
