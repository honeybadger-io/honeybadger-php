<?php

namespace Honeybadger\Tests\Mocks;

class HoneybadgerClient extends Client
{
    public function request()
    {
        return $this->calls();
    }

    public function requestBody()
    {
        if ($calls = $this->calls()) {
            $lastCall = $calls[count($calls) - 1];
            return json_decode($lastCall['request']->getBody(), true);
        }

        return null;
    }
}
