<?php

namespace Honeybadger;

use InvalidArgumentException;
use Honeybadger\Support\Repository;

class Config extends Repository
{
    /**
     * @param  array  $config
     */
    public function __construct($config = [])
    {
        $this->items = $this->mergeConfig($config);
        $this->validateRequiredKeys();
    }

    /**
     * @param  array  $config
     * @return array
     */
    private function mergeConfig($config = []) : array
    {
        return array_merge([
            'api_key' => null,
            'notifier' => [
                'name' => 'Honeybadger PHP',
                'url' => 'https://github.com/honeybadger-io/honeybadger-php',
                'version' => Honeybadger::VERSION,
            ],
            'environment' => [
                'filter' => [],
                'include' => [],
            ],
            'request' => [
                'filter' => [],
            ],
            'version' => '',
            'hostname' => gethostname(),
            'project_root' => '',
            'environment_name' => 'production',
            'handlers' => [
                'exception' => true,
                'error' => true,
            ],
            'client' => [
                'timeout' => 0,
                'proxy' => [],
            ],
            'excluded_exceptions' => [],
        ], $config);
    }

    /**
     * @return void
     *
     * @throws \InvalidArgument\Exception
     */
    private function validateRequiredKeys() : void
    {
        if (! isset($this->items['api_key']) || is_null($this->items['api_key'])) {
            throw new InvalidArgumentException('$config[\'api_key\'] is required');
        }
    }
}
