<?php

namespace Honeybadger;

use ErrorException;
use Honeybadger\Support\Arr;
use Honeybadger\Support\Repository;
use stdClass;
use Symfony\Component\HttpFoundation\Request as FoundationRequest;
use Throwable;

class ExceptionNotification
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
     * @var \Honeybadger\Breadcrumbs
     */
    protected $breadcrumbs;

    /**
     * @var \Throwable
     */
    protected $throwable;

    /**
     * @var \Honeybadger\BacktraceFactory
     */
    protected $backtrace;

    /**
     * @var \Honeybadger\Request
     */
    protected $request;

    /**
     * @var \Honeybadger\Environment
     */
    protected $environment;

    /**
     * @var array
     */
    protected $additionalParams;

    /**
     * @param \Honeybadger\Config $config
     * @param \Honeybadger\Support\Repository $context
     * @param \Honeybadger\Breadcrumbs $breadcrumbs
     */
    public function __construct(Config $config, Repository $context, Breadcrumbs $breadcrumbs)
    {
        $this->config = $config;
        $this->context = $context;
        $this->breadcrumbs = $breadcrumbs;
    }

    /**
     * @param  \Throwable  $e
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  array  $additionalParams
     * @return array
     */
    public function make(Throwable $e, FoundationRequest $request = null, array $additionalParams = []): array
    {
        $this->throwable = $e;
        $this->backtrace = $this->makeBacktrace();
        $this->request = $this->makeRequest($request);
        $this->additionalParams = $additionalParams;
        $this->environment = $this->makeEnvironment();

        return $this->format();
    }

    /**
     * @return array
     */
    private function format(): array
    {
        return [
            'breadcrumbs' => [
                'enabled' => $this->config['breadcrumbs']['enabled'],
                'trail' => $this->breadcrumbs->toArray(),
            ],
            'notifier' => $this->config['notifier'],
            'error' => [
                'class' => $this->getExceptionType(),
                'message' => $this->throwable->getMessage(),
                'backtrace' => $this->backtrace->trace(),
                'causes' => $this->backtrace->previous(),
                'fingerprint' => Arr::get($this->additionalParams, 'fingerprint', null),
                'tags' => Arr::wrap(Arr::get($this->additionalParams, 'tags', null)),
            ],
            'request' => [
                // Important to set empty maps to stdClass so they don't get JSON-encoded as arrays
                'cgi_data' => $this->environment->values() ?: new stdClass,
                'params' => $this->request->params() ?: new stdClass,
                'session' => $this->request->session() ?: new stdClass,
                'url' => $this->request->url(),
                'context' => $this->context->except(['honeybadger_component', 'honeybadger_action']) ?: new stdClass,
                'component' => Arr::get($this->additionalParams, 'component', null) ?? Arr::get($this->context, 'honeybadger_component', null),
                'action' => Arr::get($this->additionalParams, 'action', null) ?? Arr::get($this->context, 'honeybadger_action', null),
            ],
            'server' => [
                'pid' => getmypid(),
                'version' => $this->config['version'],
                'hostname' => $this->config['hostname'],
                'project_root' => $this->config['project_root'],
                'environment_name' => $this->config['environment_name'],
            ],
        ];
    }

    /**
     * @return \Honeybadger\Environment
     */
    private function makeEnvironment(): Environment
    {
        return (new Environment)
            ->filterKeys($this->config['environment']['filter'])
            ->include($this->config['environment']['include']);
    }

    /**
     * @return \Honeybadger\BacktraceFactory
     */
    private function makeBacktrace(): BacktraceFactory
    {
        return new BacktraceFactory($this->throwable, $this->config);
    }

    /**
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return \Honeybadger\Request
     */
    private function makeRequest(FoundationRequest $request = null): Request
    {
        return (new Request($request))
            ->filterKeys($this->config['request']['filter']);
    }

    private function getExceptionType(): string
    {
        // ErrorExceptions are not exceptions, but wrappers around errors.
        if ($this->throwable instanceof ErrorException) {
            $severity = $this->throwable->getSeverity();
            $severityName = $this->friendlyErrorName($severity);

            return $severity == E_DEPRECATED || $severity == E_USER_DEPRECATED
                ? "Deprecation Error ($severityName)"
                : "Error ($severityName)";
        }

        return get_class($this->throwable);
    }

    protected function friendlyErrorName(int $severity): string
    {
        // See https://www.php.net/manual/en/errorfunc.constants.php
        return [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ][$severity] ?? '';
    }
}
