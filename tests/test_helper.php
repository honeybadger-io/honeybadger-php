<?php

require_once __DIR__.'/../vendor/autoload.php';

define('FIXTURES_PATH', realpath(__DIR__.'/fixtures'));

function path_to_fixture($path)
{
	return FIXTURES_PATH.'/'.$path;
}