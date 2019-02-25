<?php

namespace Honeybadger;

use Throwable;
use Honeybadger\Support\Repository;
use Symfony\Component\HttpFoundation\Request as FoundationRequest;
use Honeybadger\Support\Arr;

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
     * @param  \Honeybadger\Config  $config
     * @param  \Honeybadger\Support\Repository  $context
     */
    public function __construct(Config $config, Repository $context)
    {
        $this->config = $config;
        $this->context = $context;
    }

    /**
     * @param  \Throwable  $e
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  array  $additionalParams
     * @return array
     */
    public function make(Throwable $e, FoundationRequest $request = null, array $additionalParams = []) : array
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
    private function format() : array
    {
        return [
            'notifier' => $this->config['notifier'],
            'error' => [
                'class' => get_class($this->throwable),
                'message' => $this->throwable->getMessage(),
                'backtrace' => $this->backtrace->trace(),
                'causes' => $this->backtrace->previous(),
            ],
            'request' => [
                'cgi_data' => $this->environment->values(),
                'params' => $this->request->params(),
                'session' => $this->request->session(),
                'url' => $this->request->url(),
                'context' => $this->context->all(),
                'component' => Arr::get($this->additionalParams, 'component', null),
                'action' => Arr::get($this->additionalParams, 'action', null),
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
    private function makeEnvironment() : Environment
    {
        return (new Environment)
            ->filterKeys($this->config['environment']['filter'])
            ->include($this->config['environment']['include']);
    }

    /**
     * @return \Honeybadger\BacktraceFactory
     */
    private function makeBacktrace() : BacktraceFactory
    {
        return new BacktraceFactory($this->throwable);
    }

    /**
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return \Honeybadger\Request
     */
    private function makeRequest(FoundationRequest $request = null) : Request
    {
        return (new Request($request))
            ->filterKeys($this->config['request']['filter']);
    }
}
