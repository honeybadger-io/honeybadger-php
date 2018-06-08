<?php

namespace Honeybadger;

use Honeybadger\Errors\NonExistentProperty;
use Honeybadger\Errors\ReadOnly;
use Honeybadger\Util\Arr;

/**
 * Retrieves, stores, and normalizes environment data from `$_SERVER` to prepare
 * information for serialization in [Notice]s. Additionally, provides
 * convenience methods for determining the URL of the request triggering an error.
 *
 * TODO: Refactor to follow better, consistent standards (Rack).
 *
 * @package  Honeybadger
 */
class Environment implements \ArrayAccess, \IteratorAggregate
{
    /**
     * Constructs and returns a new `Environment` with the supplied `$data`. If
     * no data is provided, it will be detected based on `$_SERVER` and
     * `$_COOKIE`.
     *
     * @param   array $data The data to build the environment with.
     *
     * @return  Environment   The constructed environment.
     */
    public static function factory($data = null)
    {
        return new self($data);
    }

    /**
     * @var array
     */
    private $attribute_methods = [
        'protocol', 'host', 'port', 'fullpath', 'url',
    ];

    /**
     * @var  array  List of `$_SERVER` keys to allow when building an
     *              environment automatically. Keys prefixed with `HTTP_`
     *              are also included.
     */
    private $allowed_php_environment_keys = [
        'PHP_SELF'             => null,
        'argv'                 => null,
        'argc'                 => null,
        'GATEWAY_INTERFACE'    => null,
        'SERVER_ADDR'          => null,
        'SERVER_NAME'          => null,
        'SERVER_SOFTWARE'      => null,
        'SERVER_PROTOCOL'      => null,
        'REQUEST_METHOD'       => null,
        'REQUEST_TIME'         => null,
        'REQUEST_TIME_FLOAT'   => null,
        'QUERY_STRING'         => null,
        'DOCUMENT_ROOT'        => null,
        'HTTPS'                => null,
        'REMOTE_ADDR'          => null,
        'REMOTE_HOST'          => null,
        'REMOTE_PORT'          => null,
        'REMOTE_USER'          => null,
        'REDIRECT_REMOTE_USER' => null,
        'SCRIPT_FILENAME'      => null,
        'SERVER_ADMIN'         => null,
        'SERVER_PORT'          => null,
        'SERVER_SIGNATURE'     => null,
        'PATH_TRANSLATED'      => null,
        'SCRIPT_NAME'          => null,
        'REQUEST_URI'          => null,
        'PHP_AUTH_DIGEST'      => null,
        'PHP_AUTH_USER'        => null,
        'PHP_AUTH_PW'          => null,
        'AUTH_TYPE'            => null,
        'PATH_INFO'            => null,
        'ORIG_PATH_INFO'       => null,
    ];

    /**
     * @var  array  The environment data.
     */
    private $data = [];

    /**
     * Constructs a new environment with the supplied data or attempts to detect
     * the environment using `sanitized_php_environment`.
     *
     * @param  array $data The environment data.
     */
    public function __construct($data = null)
    {
        if ($data === null) {
            $data = $this->sanitizedPhpEnvironment();
        }

        $this->data = $data;
    }

    /**
     * Determines the protocol of the request.
     *
     * @return  string  Either `http` or `https`.
     */
    public function protocol()
    {
        return (empty($this['HTTPS']) or $this['HTTPS'] == 'off') ? 'http' : 'https';
    }

    /**
     * Determines whether the request was made over HTTPS.
     *
     * @return  boolean  `true` if the request is secure, `false` otherwise.
     */
    public function isSecure()
    {
        return ($this->protocol() === 'https');
    }

    /**
     * Determines the host of the request, using the `Host` header, falling back
     * to `SERVER_NAME` if none was set.
     *
     * @return  string  The request host.
     */
    public function host()
    {
        return (empty($this['HTTP_HOST'])) ? $this['SERVER_NAME'] : $this['HTTP_HOST'];
    }

    /**
     * Determines the port of the web server. If none was found, defaults to
     * either `443` or `80` depending on whether the connection is secure.
     *
     * @return  integer  The server port.
     */
    public function port()
    {
        if (empty($this['SERVER_PORT'])) {
            return $this->isSecure() ? 443 : 80;
        }

        return $this['SERVER_PORT'];
    }

    /**
     * Determines whether the connection is using a non-standard port.
     *
     * @return  boolean  `true` if non-standard port is used, `false` otherwise.
     */
    public function isNonStandardPort()
    {
        if ($this->isSecure()) {
            return ($this->port() != 443);
        }

        return ($this->port() != 80);
    }

    /**
     * Attempts to detect the full path of the request (including query string).
     *
     * @return  String  The full path of the request.
     */
    public function fullpath()
    {
        $uri = $this['REQUEST_URI'] ?: $this['PATH_INFO'];
        $uri = preg_replace('/\?.*$/', '', $uri);
        $uri = '/' . ltrim($uri, '/');

        if (!empty($this['QUERY_STRING'])) {
            $uri .= '?' . $this['QUERY_STRING'];
        }

        return $uri;
    }

    /**
     * Returns the full request URL including protocol, host, port
     * (if non-standard), URI, and query string.
     *
     * @return String  The request URL.
     */
    public function url()
    {
        if (isset($this->data['url']) and !empty($this->data['url'])) {
            return $this->data['url'];
        }

        $url = $this->protocol . '://' . $this->host;

        if ($this->isNonStandardPort()) {
            $url .= ':' . $this->port;
        }

        $url .= $this->fullpath;

        if (!preg_match('/^https?:\/{3}$/', $url)) {
            return $url;
        }

        return null;
    }

    /**
     * Returns the environment data as an array.
     *
     * @return  array  The environment data.
     */
    public function asArray()
    {
        return $this->data;
    }

    /**
     * Alias for `as_array`.
     *
     * @return  Array  The environment data.
     */
    public function toArray()
    {
        return $this->asArray();
    }

    /**
     * Returns the JSON-encoded environment data.
     *
     * @param   integer $options Options to pass to `json_encode()`.
     *
     * @return  string   The JSON-encoded object attributes.
     */
    public function toJson($options = 0)
    {
        return json_encode($this->as_json(), $options);
    }

    /**
     * @param $key
     *
     * @return mixed
     * @throws NonExistentProperty
     */
    public function __get($key)
    {
        if (in_array($key, $this->attribute_methods)) {
            return $this->$key();
        }

        throw new NonExistentProperty($this, $key);
    }

    /**
     * @param mixed $key
     *
     * @return null
     */
    public function offsetGet($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        } elseif (in_array($key, $this->attribute_methods)) {
            return $this->$key();
        }

        return null;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     *
     * @throws ReadOnly
     */
    public function offsetSet($key, $value)
    {
        throw new ReadOnly($this);
    }

    /**
     * @param mixed $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return (array_key_exists($key, $this->data) or
            in_array($key, $this->attribute_methods));
    }

    /**
     * @param mixed $key
     *
     * @throws ReadOnly
     */
    public function offsetUnset($key)
    {
        throw new ReadOnly($this);
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * Unfortunately, PHP has no separation between the shell and
     * request environments. This means sensitive data such as database
     * information (it's common practice to set these when using services like
     * Heroku and Pagoda Box) must be filtered out.
     *
     * The following steps are taken to alleviate this issue:
     *
     * * Only allow the
     *   [predefined
     *   variables](http://php.net/manual/en/reserved.variables.server.php) in
     *   `$_SERVER`.
     *
     * @return  array  The filtered PHP request environment.
     */
    private function sanitizedPhpEnvironment()
    {
        $env = array_merge(
            $this->serverEnvironment(),
            $this->httpEnvironment()
        );

        if (!empty($_COOKIE)) {
            // Add cookies
            $env['rack.request.cookie_hash'] = $_COOKIE;
        }

        return array_filter($this->filteredEnvironment($env));
    }

    /**
     * @return array
     */
    private function serverEnvironment()
    {
        return Arr::overwrite($this->allowed_php_environment_keys, $_SERVER);
    }

    /**
     * @return array
     */
    private function httpEnvironment()
    {
        return Arr::filterKeys($_SERVER, function ($key) {
            return strpos($key, 'HTTP_') === 0;
        });
    }

    /**
     * @return  array
     */
    private function filteredEnvironment($environment)
    {
        return Arr::filterKeys($environment, function ($key) {
            return ! in_array($key, Honeybadger::$config->filter_keys);
        });
    }
}
