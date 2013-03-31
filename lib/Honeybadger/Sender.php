<?php

namespace Honeybadger;

class Sender {

	const NOTICES_URI = '/v1/notices/';

	protected static $default_headers = array(
		'Accept'       => 'application/json',
		'Content-Type' => 'application/json; charset=utf-8',
	);

	public function send_to_honeybadger($notice)
	{
	}


} // End Sender