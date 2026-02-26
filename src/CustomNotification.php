<?php

namespace Honeybadger;

use Honeybadger\Support\Arr;
use Honeybadger\Support\Repository;

class CustomNotification
{
    /**
     * @var \Honeybadger\Config
     */
    protected $config;

    /**
     * @var \Honeybadger\Support\Repository
     */
    protected $context;

    /**
     * @var \Honeybadger\Breadcrumbs
     */
    protected $breadcrumbs;

    /**
     * @param  \Honeybadger\Config  $config
     * @param  \Honeybadger\Support\Repository  $context
     * @param  \Honeybadger\Breadcrumbs  $breadcrumbs
     */
    public function __construct(Config $config, Repository $context, Breadcrumbs $breadcrumbs)
    {
        $this->config = $config;
        $this->context = $context;
        $this->breadcrumbs = $breadcrumbs;
    }

    /**
     * @param  array  $payload
     * @return array
     */
    public function make(array $payload): array
    {
        return array_merge(
            [],
            ['breadcrumbs' => [
                'enabled' => $this->config['breadcrumbs']['enabled'],
                'trail' => $this->breadcrumbs->toArray(),
            ]],
            ['notifier' => $this->config['notifier']],
            [
                'error' => [
                    'class' => $payload['title'] ?? '',
                    'message' => $payload['message'] ?? '',
                    'tags' => Arr::wrap($payload['tags'] ?? null),
                ],
            ],
            ['request' => [
                'context' => (object) $this->context->all(), ],
            ],
            ['server' => (object) [
                'environment_name' => $this->config['environment_name'],
            ]]
        );
    }
}
