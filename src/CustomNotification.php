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
        $notification = [];

        $trail = $this->breadcrumbs->toArray();
        if (! empty($trail)) {
            $notification['breadcrumbs'] = [
                'enabled' => $this->config['breadcrumbs']['enabled'],
                'trail' => $trail,
            ];
        }

        $notification['notifier'] = $this->config['notifier'];

        $error = [
            'class' => $payload['title'] ?? '',
            'message' => $payload['message'] ?? '',
        ];

        $tags = Arr::wrap($payload['tags'] ?? null);
        if (! empty($tags)) {
            $error['tags'] = $tags;
        }

        $notification['error'] = $error;
        $notification['request'] = [
            'context' => (object) $this->context->all(),
        ];
        $notification['server'] = (object) [
            'environment_name' => $this->config['environment_name'],
        ];

        return $notification;
    }
}
