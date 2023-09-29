<?php

namespace Honeybadger\Contracts;

use Honeybadger\Checkin;

interface CheckinsSync
{
    /**
     * Given an array of checkin definitions,
     * create, update, or delete them as necessary.
     *
     * The return value is an array of checkins that were created, updated or deleted.
     *
     * @param array $checkins
     * @return Checkin[] An array of checkins that were created, updated or deleted.
     */
    public function sync(array $checkins): array;
}
