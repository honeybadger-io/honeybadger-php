<?php

namespace Honeybadger\Tests;

function array_only($array, $keys)
{
    return array_intersect_key($array, array_flip((array) $keys));
}

function array_except($array, $keys)
{
    return array_diff_key($array, array_flip((array) $keys));
}

function events_config(int $sample_rate): array
{
    return [
        'api_key' => '1234',
        'events' => [
            'enabled' => true,
            'sample_rate' => $sample_rate
        ]
    ];
}
