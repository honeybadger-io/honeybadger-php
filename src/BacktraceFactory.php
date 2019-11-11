<?php

namespace Honeybadger;

use ReflectionClass;
use ReflectionException;
use Throwable;

class BacktraceFactory
{
    /**
     * @var \Throwable
     */
    protected $exception;

    /**
     * @param   \Throwable  $exception
     */
    public function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }

    /**
     * @return array
     */
    public function trace() : array
    {
        $backtrace = $this->offsetForThrownException(
            $this->exception->getTrace()
        );

        return $this->formatBacktrace($backtrace);
    }

    /**
     * @return array
     */
    public function previous() : array
    {
        return $this->formatPrevious($this->exception);
    }

    /**
     * @param  \Throwable  $e
     * @param  array  $previousCauses
     * @return array
     */
    private function formatPrevious(Throwable $e, array $previousCauses = []) : array
    {
        if ($e = $e->getPrevious()) {
            $previousCauses[] = [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'backtrace' => (new self($e))->trace(),
            ];

            return $this->formatPrevious($e, $previousCauses);
        }

        return $previousCauses;
    }

    /**
     * @param  array  $backtrace
     * @return array
     */
    private function offsetForThrownException(array $backtrace) : array
    {
        $backtrace[0] = array_merge($backtrace[0] ?? [], [
            'line' => $this->exception->getLine(),
            'file' => $this->exception->getFile(),
        ]);

        return $backtrace;
    }

    /**
     * @param  array  $backtrace
     * @return array
     */
    private function formatBacktrace(array $backtrace) : array
    {
        return array_map(function ($frame) {
            if (! array_key_exists('file', $frame)) {
                $context = $this->contextWithoutFile($frame);
            } else {
                $context = $this->contextWithFile($frame);
            }

            return array_merge($context, [
                'method' => $frame['function'] ?? null,
                'args' => $this->parseArgs($frame['args'] ?? []),
                'class' => $frame['class'] ?? null,
                'type' => $frame['type'] ?? null,
            ]);
        }, $backtrace);
    }

    /**
     * Parse method arguments and make any transformations.
     *
     * @param array $args
     * @return array
     */
    private function parseArgs(array $args) : array
    {
        return array_map(function ($arg) {
            if (is_object($arg)) {
                return get_class($arg);
            }

            return $arg;
        }, $args);
    }

    /**
     * @param  array  $frame
     * @return array
     */
    private function contextWithoutFile(array $frame) : array
    {
        if (! empty($frame['class'])) {
            $filename = sprintf('%s%s%s', $frame['class'], $frame['type'], $frame['function']);

            try {
                $reflect = new ReflectionClass($frame['class']);
                $filename = $reflect->getFileName();
            } catch (ReflectionException $e) {
                // Forget it if we run into errors, it's not worth it.
            }
        } elseif (! empty($frame['function'])) {
            $filename = sprintf('%s(anonymous)', $frame['function']);
        } else {
            $filename = sprintf('(anonymous)');
        }

        if (empty($filename)) {
            $filename = '[Anonymous function]';
        }

        return [
            'source' => null,
            'file' => $filename,
            'number' => '0',
        ];
    }

    /**
     * @param  array  $frame
     * @return array
     */
    private function contextWithFile(array $frame) : array
    {
        return [
            'source' => (new FileSource($frame['file'], $frame['line']))->getSource(),
            'file' => $frame['file'],
            'number' => (string) $frame['line'],
        ];
    }
}
