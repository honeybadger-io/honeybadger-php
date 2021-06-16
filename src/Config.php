<?php

namespace Honeybadger;

use Honeybadger\Exceptions\ServiceException;
use Honeybadger\Support\Repository;

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
            'endpoint' => Honeybadger::API_URL,
            'notifier' => [
                'name' => 'honeybadger-php',
                'url' => 'https://github.com/honeybadger-io/honeybadger-php',
                'version' => Honeybadger::VERSION,
            ],
            'environment_name' => 'production',
            'report_data' => true,
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
            'handlers' => [
                'exception' => true,
                'error' => true,
            ],
            'client' => [
                'timeout' => 15,
                'proxy' => [],
                'verify' => true,
            ],
            'excluded_exceptions' => [],
            'vendor_paths' => [
                'vendor\/.*',
            ],
            'breadcrumbs' => [
                'enabled' => true,
            ],
        ], $config);
    }
}
