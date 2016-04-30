<?php

require 'vendor/autoload.php';

use \Slim\Slim;

// Load our shared Honeybadger config.
$options = include __DIR__.'/../config.php';

// Create a Slim app with a few settings.
$app = new Slim(array(
    'debug' => true,
    'mode'  => 'examples',
));

// Configure Honeybadger to integrate with our app.
$app->add(new Honeybadger\Slim(array(
	'api_key'           => $options['api_key'],
	'http_open_timeout' => 15,
	'http_read_timeout' => 15,
	'debug'             => true,
	'project_root'      => realpath(__DIR__),
)));

// Make some routes:

$app->get('/', function() {
	echo sprintf('<a href="%s/%s">%s</a>', '/slim/fail', rand(0, 999999),
		'trigger an error');
});

$app->get('/fail/:id', function($id) {
	throw new Exception('bleh! '.$id);
});

// Run the app.
$app->run();
