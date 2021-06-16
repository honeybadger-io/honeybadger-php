<?php

namespace Honeybadger\Tests\Mocks;

class HoneybadgerClient extends Client
{
    public function request()
    {
        return $this->calls();
    }

    /**
     * @return \GuzzleHttp\Psr7\Request|null
     */
    public function getLatestRequest()
    {
        if ($calls = $this->calls()) {
            return $calls[count($calls) - 1]['request'];
        }

        return null;
    }

    public function requestBody()
    {
        if ($latestRequest = $this->getLatestRequest()) {
            return json_decode($latestRequest->getBody(), true);
        }

        return null;
    }
}
