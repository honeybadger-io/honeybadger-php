<?php

namespace Honeybadger;

use Honeybadger\Support\Repository;
use InvalidArgumentException;

class RawNotification
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
     *
     * @throws \InvalidArgumentException
     */
    public function make(array $payload) : array
    {
        $payload = array_merge(
            [],
            ['notifier' => $this->config['notifier']],
            ['error' => []],
            ['request' => ['context' => (object) $this->context->all()],
            ],
            ['server' => (object) []],
            $payload
        );

        $this->validatePayload($payload);

        return $payload;
    }

    /**
     * @param  array  $payload
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function validatePayload(array $payload) : void
    {
        if (empty($payload['error']['class'])) {
            throw new InvalidArgumentException('The notification error.class field is required');
        }

        if (empty($payload['notifier']['name'])) {
            throw new InvalidArgumentException('The notification notifier.name field is required');
        }
    }
}
