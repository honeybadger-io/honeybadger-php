<?php

namespace Honeybadger\Tests\Mocks;

class HoneybadgerClient extends Client
{
    public function request()
    {
        return $this->calls()[0]['request'];
    }

    public function requestBody()
    {
        return $this->calls()
            ? json_decode($this->calls()[0]['request']->getBody(), true)
            : null;
    }
}
