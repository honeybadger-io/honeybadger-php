<?php

namespace Honeybadger;

use GuzzleHttp\Client;

class GuzzleFactory
{
  public static function make($options)
  {
    return new Client($options);
  }
}
