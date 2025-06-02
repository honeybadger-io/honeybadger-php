<?php

namespace Honeybadger;

use DateTime;
use ErrorException;
use GuzzleHttp\Client;
use Honeybadger\Concerns\Newable;
use Honeybadger\Contracts\Reporter;
use Honeybadger\Exceptions\ServiceException;
use Honeybadger\Handlers\BeforeEventHandler;
use Honeybadger\Handlers\BeforeNotifyHandler;
use Honeybadger\Handlers\ErrorHandler;
use Honeybadger\Handlers\ExceptionHandler;
use Honeybadger\Handlers\ShutdownHandler;
use Honeybadger\Support\Repository;
use Symfony\Component\HttpFoundation\Request as FoundationRequest;
use Throwable;

class Honeybadger implements Reporter
{
    use Newable;

    /**
     * SDK Version.
     */
    const VERSION = '2.24.1';

    /**
     * Honeybadger API URL.
     * Used to send notices (errors), events (Insights), and check-ins.
     * If you are using our EU stack, this should be set to "https://eu-api.honeybadger.io/".
     */
    const API_URL = 'https://api.honeybadger.io/';

    /**
     * Honeybadger APP URL.
     * Used to configure/synchronize check-ins.
     * If you are using our EU stack, this should be set to "https://eu-app.honeybadger.io/".
     */
    const APP_URL = 'https://app.honeybadger.io/';

    /**
     * @var CheckInsClient;
     */
    protected $checkInsClient;

    /**
     * @var HoneybadgerClient;
     */
    protected $client;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Repository
     */
    protected $context;

    /**
     * @var Breadcrumbs
     */
    protected $breadcrumbs;

    /**
     * @var BulkEventDispatcher
     */
    protected $events;

    /**
     * @var BeforeNotifyHandler
     */
    protected $beforeNotifyHandlers;

    /**
     * @var BeforeEventHandler
     */
    protected $beforeEventHandlers;

    public function __construct(array $config = [], ?Client $client = null, ?BulkEventDispatcher $eventsDispatcher = null)
    {
        $this->config = new Config($config);

        $this->client = new HoneybadgerClient($this->config, $client);
        $this->checkInsClient = new CheckInsClientWithErrorHandling($this->config, $client);
        $this->context = new Repository;
        $this->breadcrumbs = new Breadcrumbs(40);
        $this->events = $eventsDispatcher ?? new BulkEventDispatcher($this->config, $this->client);

        $this->setHandlers();
    }

    /**
     * {@inheritdoc}
     */
    public function notify(Throwable $throwable, ?FoundationRequest $request = null, array $additionalParams = []): array
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

        $notification = $notification->make($throwable, $request, $additionalParams);
        if (!$this->beforeNotifyHandlers->handle($notification)) {
            return [];
        }

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
    public function checkin(string $idOrSlug): void
    {
        if ($this->isCheckInSlug($idOrSlug)) {
            $this->client->checkInWithSlug($this->config->get('api_key'), $idOrSlug);
            return;
        }

        $this->client->checkIn($idOrSlug);
    }

    private function isCheckInSlug(string $idOrSlug): bool
    {
        $checkIns = $this->config->get('checkins') ?? [];
        if (count($checkIns) > 0) {
            $filtered = array_filter($checkIns, function ($checkIn) use ($idOrSlug) {
                return $checkIn->slug === $idOrSlug;
            });
            return count($filtered) > 0;
        }

        return false;
    }

    /**
     * @throws ServiceException
     */
    private function getCheckInByName(string $projectId, string $name): ?CheckIn {
        $checkIns = $this->checkInsClient->listForProject($projectId) ?? [];
        $filtered = array_filter($checkIns, function ($checkIn) use ($name) {
            return $checkIn->name === $name;
        });
        if (count($filtered) > 0) {
            return array_values($filtered)[0];
        }

        return null;
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
        if ($this->config['breadcrumbs']['enabled'] && !empty($message)) {
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
     * {@inheritdoc}
     */
    public function event($eventTypeOrPayload, ?array $payload = null): void
    {
        if (empty($this->config['api_key']) || ! $this->config['events']['enabled']) {
            return;
        }

        if (is_array($eventTypeOrPayload)) {
            $payload = $eventTypeOrPayload;
        } else {
            $payload = $payload ?? [];
            $payload['event_type'] = $eventTypeOrPayload;
        }

        if (empty($payload)) {
            return;
        }

        $event = array_merge(
            ['ts' => (new DateTime())->format(DATE_RFC3339_EXTENDED)],
            $payload
        );

        // if 'ts' is set, we need to make sure it's a string in the correct format
        if ($event['ts'] instanceof DateTime) {
            $event['ts'] = $event['ts']->format(DATE_RFC3339_EXTENDED);
        }

        if (!$this->beforeEventHandlers->handle($event)) {
            return;
        }

        // Apply sampling after before_event callbacks
        if (!$this->shouldSampleEvent($event)) {
            return;
        }

        // Remove internal metadata before sending
        if (isset($event['_hb'])) {
            unset($event['_hb']);
        }

        $this->events->addEvent($event);
    }

    /**
     * {@inheritdoc}
     */
    public function flushEvents(): void
    {
        if (!$this->config['events']['enabled']) {
            return;
        }

        $this->events->flushEvents();
    }

    /**
     * {@inheritDoc}
     */
    public function beforeNotify(callable $callback): void
    {
        $this->beforeNotifyHandlers->register($callback);
    }

    /**
     * {@inheritDoc}
     */
    public function beforeEvent(callable $callback): void
    {
        $this->beforeEventHandlers->register($callback);
    }

    /**
     * @return Repository
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
        $this->beforeNotifyHandlers = new BeforeNotifyHandler();
        $this->beforeEventHandlers = new BeforeEventHandler();

        if ($this->config['handlers']['exception']) {
            (new ExceptionHandler($this))->register();
        }

        if ($this->config['handlers']['error']) {
            (new ErrorHandler($this))->register();
        }

        if ($this->config['handlers']['shutdown']) {
            (new ShutdownHandler($this))->register();
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

    /**
     * Determines if an event should be sampled based on sampling rate configuration
     * and any override in the event payload.
     *
     * @param array $event The event payload
     * @return bool Whether the event should be sent
     */
    protected function shouldSampleEvent(array $event): bool
    {
        // Get the configured sampling rate (0-100)
        $samplingRate = $this->config['events']['sample_rate'] ?? 100;

        // Check for override in event payload
        if (isset($event['_hb']['sample_rate']) && is_numeric($event['_hb']['sample_rate'])) {
            $samplingRate = (int) $event['_hb']['sample_rate'];
        }

        // If sampling rate is 0, don't send any events
        if ($samplingRate <= 0) {
            return false;
        }

        // If sampling rate is 100 or greater, send all events
        if ($samplingRate >= 100) {
            return true;
        }

        // If requestId is present, use it for consistent sampling
        if (isset($event['requestId'])) {
            // Use CRC32 of requestId for consistent sampling,
            // and use sprintf to convert to unsigned integer
            $crc = sprintf('%u', crc32((string) $event['requestId']));
            return $crc % 100 < $samplingRate;
        }

        // Otherwise, use random sampling
        return mt_rand(0, 99) < $samplingRate;
    }
}
