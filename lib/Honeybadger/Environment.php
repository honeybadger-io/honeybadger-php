<?php

namespace Honeybadger;

use \Honeybadger\Util\Arr;
use \Honeybadger\Util\SemiOpenStruct;
use \Honeybadger\Errors\NonExistentProperty;
use \Honeybadger\Errors\ReadOnly;

/**
 * Retrieves, stores, and normalizes environment data from `$_SERVER` to prepare
 * information for serialization in [Notice]s. Additionally, provides
 * convenience methods for determining the URL of the request trigging an error.
 *
 * TODO: Refactor to follow better, consistent standards (Rack).
 */
class Environment implements \ArrayAccess, \IteratorAggregate {

	public static function factory($data = NULL)
	{
		return new self($data);
	}

	private $_attribute_methods = array(
		'protocol', 'host', 'port', 'fullpath', 'url',
	);

	private $data = array();

	private function __construct($data = NULL)
	{
		if ($data === NULL)
		{
			$data = Arr::merge($_SERVER, array(
				'rack.request.cookie_hash' => empty($_COOKIE) ? NULL : $_COOKIE,
			));
		}

		$this->data = $data;
	}

	public function protocol()
	{
		return (empty($this['HTTPS']) OR $this['HTTPS'] == 'off') ? 'http' : 'https';
	}

	public function is_secure()
	{
		return ($this->protocol() === 'https');
	}

	public function host()
	{
		return (empty($this['HTTP_HOST'])) ? $this['SERVER_NAME'] : $this['HTTP_HOST'];
	}

	public function port()
	{
		if (empty($this['SERVER_PORT']))
		{
			return $this->is_secure() ? 443 : 80;
		}
		else
		{
			return $this['SERVER_PORT'];
		}
	}

	public function is_non_standard_port()
	{
		if ($this->is_secure())
		{
			return ($this->port() != 443);
		}
		else
		{
			return ($this->port() != 80);
		}
	}

	/**
	 * Attempts to detect the full URL of the request.
	 */
	public function fullpath()
	{
		$uri = $this['REQUEST_URI'] ?: $this['PATH_INFO'];
		$uri = preg_replace('/\?.*$/i', '', $uri);
		$uri = '/'.ltrim($uri, '/');

		if ( ! empty($this['QUERY_STRING']))
		{
			$uri .= '?'.$this['QUERY_STRING'];
		}

		return $uri;
	}

	public function url()
	{
		if (isset($this->data['url']) AND ! empty($this->data['url']))
			return $this->data['url'];

		$url = $this->protocol.'://'.$this->host;

		if ($this->is_non_standard_port())
		{
			$url .= ':'.$this->port;
		}

		$url .= $this->fullpath;

		if ( ! preg_match('/^https?:\/{3}$/', $url))
			return $url;
	}

	public function as_array()
	{
		return $this->data;
	}

	public function as_json()
	{
		return $this->as_array();
	}

	public function to_json()
	{
		return json_encode($this->as_array());
	}

	public function __get($key)
	{
		if (in_array($key, $this->_attribute_methods))
		{
			return $this->$key();
		}
		else
		{
			throw new NonExistentProperty($this, $key);
		}
	}

	public function offsetGet($key)
	{
		if (array_key_exists($key, $this->data))
		{
			return $this->data[$key];
		}
		elseif (in_array($key, $this->_attribute_methods))
		{
			return $this->$key();
		}
		else
		{
			return NULL;
		}
	}

	public function offsetSet($key, $value)
	{
		throw new ReadOnly($this);
	}

	public function offsetExists($key)
	{
		return (array_key_exists($key, $this->data) OR
		        in_array($key, $this->_attribute_methods));
	}

	public function offsetUnset($key)
	{
		throw new ReadOnly($this);
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->data);
	}

}