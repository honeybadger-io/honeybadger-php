<?php

namespace Honeybadger;

use ErrorException;
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
    const VERSION = '2.13.0';

    /**
     * Honeybadger API URL.
     */
    const API_URL = 'https://api.honeybadger.io/';

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
     * @var \Honeybadger\Breadcrumbs
     */
    protected $breadcrumbs;

    public function __construct(array $config = [], Client $client = null)
    {
        $this->config = new Config($config);

        $this->client = new HoneybadgerClient($this->config, $client);
        $this->context = new Repository;
        $this->breadcrumbs = new Breadcrumbs(40);

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

        $notification = new ExceptionNotification($this->config, $this->context, $this->breadcrumbs);

        if ($this->config['breadcrumbs']['enabled']) {
            $this->addBreadcrumb('Honeybadger Notice', [
                'message' => $throwable->getMessage(),
                'name' => get_class($throwable),
            ], 'notice');
        }

        return $this->client->notification(
            $notification->make($throwable, $request, $additionalParams)
        );
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
     * {@inheritdoc}
     */
    public function context($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $contextKey => $contextValue) {
                $this->context->set($contextKey, $contextValue);
            }
        } else {
            $this->context->set($key, $value);
        }

        return $this;
    }

    /**
     * @return void
     */
    public function resetContext(): void
    {
        $this->context = new Repository;
    }

    public function addBreadcrumb(string $message, array $metadata = [], string $category = 'custom'): Reporter
    {
        if ($this->config['breadcrumbs']['enabled']) {
            $this->breadcrumbs->add([
                'message' => $message,
                'metadata' => $metadata,
                'category' => $category,
            ]);
        }

        return $this;
    }

    public function clear(): Reporter
    {
        $this->context = new Repository;
        $this->breadcrumbs->clear();

        return $this;
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
    protected function excludedException(Throwable $throwable): bool
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
    protected function shouldReport(Throwable $throwable): bool
    {
        if ($throwable instanceof ErrorException
            && in_array($throwable->getSeverity(), [E_DEPRECATED, E_USER_DEPRECATED])
            && $this->config['capture_deprecations'] == false) {
            return false;
        }

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
