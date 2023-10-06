<?php

namespace Honeybadger;

use GuzzleHttp\Client;
use Honeybadger\Exceptions\ServiceException;
use Throwable;

class CheckinsClientProxy
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CheckinsClient
     */
    private $baseClient;

    public function __construct(Config $config, Client $httpClient = null)
    {
        $this->config = $config;
        $this->baseClient = new CheckinsClient($config, $httpClient);
    }

    /**
     * @return mixed|null
     */
    public function __call($name, $arguments)
    {
        try {
            return $this->baseClient->{$name}(...$arguments);
        }
        catch (ServiceException $e) {
            $this->handleServiceException($e);
        }
        catch (Throwable $e) {
            $this->handleServiceException(ServiceException::generic($e));
        }

        return null;
    }

    protected function handleServiceException(ServiceException $e): void
    {
        $serviceExceptionHandler = $this->config['service_exception_handler'];
        call_user_func_array($serviceExceptionHandler, [$e]);
    }
}
