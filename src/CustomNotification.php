<?php

namespace Honeybadger;

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
     * @param  \Honeybadger\Config  $config
     * @param  \Honeybadger\Support\Repository  $context
     */
    public function __construct(Config $config, Repository $context)
    {
        $this->config = $config;
        $this->context = $context;
    }

    /**
     * @param  array  $payload
     * @return array
     */
    public function make(array $payload) : array
    {
        return array_merge(
            [],
            ['notifier' => $this->config['notifier']],
            [
                'error' => [
                    'class' => $payload['title'] ?? '',
                    'message' => $payload['message'] ?? '',
                ],
            ],
            ['request' => [
                'context' => (object) $this->context->all(), ],
            ],
            ['server' => (object) []]
        );
    }
}
