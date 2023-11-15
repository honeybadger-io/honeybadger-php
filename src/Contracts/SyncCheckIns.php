<?php

namespace Honeybadger\Contracts;

use Honeybadger\CheckIn;

interface SyncCheckIns
{
    /**
     * Given an array of checkin definitions,
     * create, update, or delete them as necessary.
     *
     * The return value is an array of checkins that were created, updated or deleted.
     *
     * @param array $checkIns
     * @return CheckIn[] An array of checkins that were created, updated or deleted.
     */
    public function sync(array $checkIns): array;
}
