<?php

namespace Honeybadger;

class BulkEventDispatcher
{
    const BULK_THRESHOLD = 100;
    const DISPATCH_INTERVAL_SECONDS = 2;

    /**
     * @var HoneybadgerClient
     */
    private $client;

    /**
     * @var array
     */
    private $events = [];

    /**
     * @var int
     */
    private $maxEvents;

    /**
     * @var int
     */
    private $dispatchInterval;

    /**
     * @var int
     */
    private $lastDispatchTime;

    public function __construct(Config $config, HoneybadgerClient $client)
    {
        $this->client = $client;
        $eventsConfig = $config->get('events') ?? [];
        $this->maxEvents = $eventsConfig['bulk_threshold'] ?? self::BULK_THRESHOLD;
        $this->dispatchInterval = $eventsConfig['dispatch_interval_seconds'] ?? self::DISPATCH_INTERVAL_SECONDS;
        $this->lastDispatchTime = time();
    }

    public function addEvent($event)
    {
        $this->events[] = $event;

        if (count($this->events) >= $this->maxEvents || (time() - $this->lastDispatchTime) >= $this->dispatchInterval) {
            $this->dispatchEvents();
        }
    }

    public function flushEvents()
    {
        if (!$this->hasEvents()) {
            return;
        }

        $this->dispatchEvents();
    }

    public function hasEvents(): bool {
        return !empty($this->events);
    }

    private function dispatchEvents()
    {
        if (!$this->hasEvents()) {
            return;
        }

        $this->client->events($this->events);

        $this->events = [];
        $this->lastDispatchTime = time();
    }
}
