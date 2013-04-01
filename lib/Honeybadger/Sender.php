<?php

namespace Honeybadger;

use \Honeybadger\Util\SemiOpenStruct;
use \Guzzle\Http\Client;

class Sender extends SemiOpenStruct {

	const NOTICES_URI = '/v1/notices/';

	protected static $default_headers = array(
		'Accept'       => 'application/json',
		'Content-Type' => 'application/json; charset=utf-8',
	);

	public function send_to_honeybadger($notice)
	{
		if ($notice instanceof Notice)
		{
			$data = $notice->to_json();
		}
		else
		{
			$data = (string) $notice;
		}

		$headers = array();
		if ($api_key = Honeybadger::$config->api_key)
		{
			$headers['X-API-Key'] = $api_key;
		}

		$response = $this->setup_http_client()
		                 ->post(self::NOTICES_URI, $headers, $data)
		                 ->send();

		$body = $response->json();

		return $body['id'];
	}

	private function setup_http_client()
	{
		// Fetch a copy of the configuration.
		$config = Honeybadger::$config;

		$options = array(
			'curl.options' => array(
				// Timeouts
				'CURLOPT_CONNECTTIMEOUT' => $config->http_open_timeout,
				'CURLOPT_TIMEOUT'        => $config->http_read_timeout,
				// Location redirects
				'CURLOPT_AUTOREFERER'    => TRUE,
				'CURLOPT_FOLLOWLOCATION' => TRUE,
				'CURLOPT_MAXREDIRS'      => 10,
			),
		);

		if ($config->proxy_host)
		{
			$options['curl.options']['CURLOPT_HTTPPROXYTUNNEL'] = TRUE;
			$options['curl.options']['CURLOPT_PROXY']           = $config->proxy_host;
			$options['curl.options']['CURLOPT_PROXYPORT']       = $config->proxy_user.':'.$config->proxy_pass;
		}

		if ($config->secure)
		{
			$options['ssl.certificate_authority'] = $config->certificate_authority;
		}

		try
		{
			$client = new Client($config->base_url(), $options);
			$client->setDefaultHeaders(self::$default_headers);
			$client->setUserAgent($this->user_agent());

			return $client;
		}
		catch (Exception $e)
		{
			// $this->log(Logger::ERROR, '['.__CLASS__.'::setup_http_client] Failure initializing the request client. Error: [ '.$e->getCode().' ] '.$e->getMessage());

			// Rethrow the exception
			throw $e;
		}
	}

	private function user_agent()
	{
		return sprintf('%s v%s (%s)', Honeybadger::NOTIFIER_NAME,
			Honeybadger::VERSION, Honeybadger::NOTIFIER_URL);
	}

} // End Sender