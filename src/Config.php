<?php

namespace Honeybadger;

use Honeybadger\Support\Repository;
use Honeybadger\Exceptions\ServiceException;

class Config extends Repository
{
    /**
     * @param  array  $config
     */
    public function __construct($config = [])
    {
        $this->items = $this->mergeConfig($config);
    }

    /**
     * @param array $config
     *
     * @return array
     */
    private function mergeConfig($config = []): array
    {
        return array_merge([
            'api_key' => null,
            'notifier' => [
                'name' => 'Honeybadger PHP',
                'url' => 'https://github.com/honeybadger-io/honeybadger-php',
                'version' => Honeybadger::VERSION,
            ],
            'service_exception_handler' => function (ServiceException $e) {
                throw $e;
            },
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
                'verify' => true,
            ],
            'excluded_exceptions' => [],
            'report_data' => true,
            'vendor_paths' => [
                'vendor\/.*',
            ],
        ], $config);
    }
}
