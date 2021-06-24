<?php

namespace Honeybadger;

use Honeybadger\Contracts\Reporter;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class LogHandler extends AbstractProcessingHandler
{
    /**
     * @var \Honeybadger\Contracts\Reporter
     */
    protected $honeybadger;

    /**
     * @param \Honeybadger\Contracts\Reporter $honeybadger
     * @param int $level
     * @param bool $bubble
     */
    public function __construct(Reporter $honeybadger, int $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->honeybadger = $honeybadger;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        $this->honeybadger->rawNotification(function ($config) use ($record) {
            return [
                'notifier' => array_merge($config['notifier'], ['name' => 'Honeybadger Log Handler']),
                'error' => $this->getHoneybadgerErrorFromMonologRecord($record, $config),
                'request' => [
                    'context' => $this->getHoneybadgerContextFromMonologRecord($record),
                ],
                'server' => [
                    'environment_name' => $config['environment_name'],
                    'time' => $record['datetime']->format("Y-m-d\TH:i:sP"),
                ],
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatter(): FormatterInterface
    {
        return new LineFormatter('[%datetime%] %channel%.%level_name%: %message%');
    }

    protected function getHoneybadgerErrorFromMonologRecord(array $record, $config): array
    {
        $error = [
            'tags' => [
                'log',
                sprintf('%s.%s', $record['channel'], $record['level_name']),
            ],
            'fingerprint' => md5($record['message']),
        ];
        $e = $record['context']['exception'] ?? null;
        if ($e instanceof \Throwable) {
            $error['class'] = get_class($e);
            $error['message'] = $e->getMessage();
            $error['backtrace'] = (new BacktraceFactory($e, $config))->trace();
        } else {
            $error['class'] = "{$record['level_name']} Log";
            $error['message'] = $record['message'];
        }

        return $error;
    }

    protected function getHoneybadgerContextFromMonologRecord(array $record): array
    {
        $context = $record['context'];
        $context['level_name'] = $record['level_name'];
        $context['log_channel'] = $record['channel'];

        $e = $context['exception'] ?? null;
        if ($e && $e instanceof \Throwable) {
            // Format Exception objects properly
            $context['exception'] = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }

        return $context;
    }
}
