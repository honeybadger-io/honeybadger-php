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
