<?php

namespace Honeybadger;

use Honeybadger\Contracts\Reporter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;

class LogEventHandler extends AbstractProcessingHandler
{
    /**
     * @var \Honeybadger\Contracts\Reporter
     */
    protected $honeybadger;

    /**
     * @param \Honeybadger\Contracts\Reporter $honeybadger
     * @param $level
     * @param bool $bubble
     */
    public function __construct(Reporter $honeybadger, $level = Logger::INFO, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->honeybadger = $honeybadger;
    }

    /**
     * @param array|\Monolog\LogRecord $record
     */
    protected function write($record): void
    {
        if (!$this->isHandling($record)) {
            return;
        }

        $eventPayload = $this->getEventPayloadFromMonologRecord($record);
        $this->honeybadger->event('log', $eventPayload);
    }

    protected function getEventPayloadFromMonologRecord(LogRecord $record): array {
        $payload = [
            'ts' => $record->datetime->format(DATE_ATOM),
            'severity' => strtolower($record->level->getName()),
            'message' => $record->message,
            'channel' => $record->channel,
        ];

        if (isset($record->context) && $record->context != null) {
            $payload = array_merge($payload, $record->context);
        }

        return $payload;
    }
}
