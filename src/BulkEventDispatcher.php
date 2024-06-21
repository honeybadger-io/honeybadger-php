<?php

namespace Honeybadger;

class BulkEventDispatcher
{
    const BULK_THRESHOLD = 50;
    const DISPATCH_INTERVAL_MS = 100;

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
        $this->dispatchInterval = $eventsConfig['dispatch_interval_ms'] ?? self::DISPATCH_INTERVAL_MS;
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
        if (empty($this->events)) {
            return;
        }

        $this->dispatchEvents();
    }

    private function dispatchEvents()
    {
        if (empty($this->events)) {
            return;
        }

        $this->client->events($this->events);

        $this->events = [];
        $this->lastDispatchTime = time();
    }
}
