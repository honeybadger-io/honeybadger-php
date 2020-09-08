<?php

namespace Honeybadger;

use GuzzleHttp\Client;
use Honeybadger\Concerns\Newable;
use Honeybadger\Contracts\Reporter;
use Honeybadger\Exceptions\ServiceException;
use Honeybadger\Handlers\ErrorHandler;
use Honeybadger\Handlers\ExceptionHandler;
use Honeybadger\Support\Repository;
use Symfony\Component\HttpFoundation\Request as FoundationRequest;
use Throwable;

class Honeybadger implements Reporter
{
    use Newable;

    /**
     * SDK Version.
     */
    const VERSION = '2.2.0';

    /**
     * Honeybadger API URL.
     */
    const API_URL = 'https://api.honeybadger.io/v1/';

    /**
     * @var \Honeybadger\HoneybadgerClient;
     */
    protected $client;

    /**
     * @var \Honeybadger\Config
     */
    protected $config;

    /**
     * @var \Honeybadger\Support\Repository
     */
    protected $context;

    /**
     * @param  array  $config
     * @param  \GuzzleHttp\Client  $client
     */
    public function __construct(array $config = [], Client $client = null)
    {
        $this->config = new Config($config);

        $this->client = new HoneybadgerClient($this->config, $client);
        $this->context = new Repository;

        $this->setHandlers();
    }

    /**
     * {@inheritdoc}
     */
    public function notify(Throwable $throwable, FoundationRequest $request = null, array $additionalParams = []): array
    {
        if (! $this->shouldReport($throwable)) {
            return [];
        }

        $notification = (new ExceptionNotification($this->config, $this->context))
            ->make($throwable, $request, $additionalParams);

        return $this->client->notification($notification);
    }

    /**
     * {@inheritdoc}
     */
    public function customNotification(array $payload): array
    {
        if (empty($this->config['api_key']) || ! $this->config['report_data']) {
            return [];
        }

        $notification = (new CustomNotification($this->config, $this->context))
            ->make($payload);

        return $this->client->notification($notification);
    }

    /**
     * {@inheritdoc}
     */
    public function rawNotification(callable $callable): array
    {
        if (empty($this->config['api_key']) || ! $this->config['report_data']) {
            return [];
        }

        $notification = (new RawNotification($this->config, $this->context))
            ->make($callable($this->config, $this->context));

        return $this->client->notification($notification);
    }

    /**
     * {@inheritdoc}
     */
    public function checkin(string $key): void
    {
        $this->client->checkin($key);
    }

    /**
     * @param  int|string  $key
     * @param  int|string|array  $value
     * @return void
     */
    public function context($key, $value): void
    {
        $this->context->set($key, $value);
    }

    /**
     * @return void
     */
    public function resetContext(): void
    {
        $this->context = new Repository;
    }

    /**
     * @return \Honeybadger\Support\Repository
     */
    public function getContext(): Repository
    {
        return $this->context;
    }

    /**
     * @return void
     */
    private function setHandlers(): void
    {
        if ($this->config['handlers']['exception']) {
            (new ExceptionHandler($this))->register();
        }

        if ($this->config['handlers']['error']) {
            (new ErrorHandler($this))->register();
        }
    }

    /**
     * @param  \Throwable  $throwable
     * @return bool
     */
    private function excludedException(Throwable $throwable): bool
    {
        return $throwable instanceof ServiceException
            || in_array(
                get_class($throwable),
                $this->config['excluded_exceptions']
            );
    }

    /**
     * @param  \Throwable  $throwable
     * @return bool
     */
    private function shouldReport(Throwable $throwable): bool
    {
        return ! $this->excludedException($throwable)
            && ! empty($this->config['api_key'])
            && $this->config['report_data'];
    }

    /**
     * @param string $component
     * @return self
     */
    public function setComponent(string $component): self
    {
        $this->context('honeybadger_component', $component);

        return $this;
    }

    /**
     * @param string $action
     * @return self
     */
    public function setAction(string $action): self
    {
        $this->context('honeybadger_action', $action);

        return $this;
    }
}
