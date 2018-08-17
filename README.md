# Honeybadger PHP library

[![Latest Version on Packagist](https://img.shields.io/packagist/v/honeybadger-io/honeybadger-php.svg?style=flat-square)](https://packagist.org/packages/honeybadger-io/honeybadger-php)
[![Total Downloads](https://img.shields.io/packagist/dt/honeybadger-io/honeybadger-php.svg?style=flat-square)](https://packagist.org/packages/honeybadger-io/honeybadger-php)
[![Build Status](https://img.shields.io/travis/honeybadger-io/honeybadger-php/master.svg?style=flat-square)](https://travis-ci.org/honeybadger-io/honeybadger-php)
[![Quality Score](https://img.shields.io/scrutinizer/g/honeybadger-io/honeybadger-php.svg?style=flat-square)](https://scrutinizer-ci.com/g/honeybadger-io/honeybadger-php)
[![Maintainability](https://api.codeclimate.com/v1/badges/5dfccab91329acaa9444/maintainability)](https://codeclimate.com/github/honeybadger-io/honeybadger-php/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/5dfccab91329acaa9444/test_coverage)](https://codeclimate.com/github/honeybadger-io/honeybadger-php/test_coverage)
[![StyleCI](https://styleci.io/repos/9077424/shield)](https://github.styleci.io/repos/9077424)

This is the client library for integrating apps with the :zap: [Honeybadger Exception Notifier for PHP](http://honeybadger.io).

## Framework Integrations

* Laravel - [honeybadger-io/honeybadger-laravel](https://github.com/honeybadger-io/honeybadger-laravel)

## Installation
You can install the package via composer:

```bash
composer require honeybadger-io/honeybadger-php
```

## Usage
### Send Exception Notification
```php
$honeybadger = Honeybadger::new([
    'api_key' => 'abc123'
]);

try {
    throw new Exception('Whoops!');
} catch (Exception $e) {
    // You can optionally include your own
    // \Symfony\Component\HttpFoundation\Request::class request.
    $honeybadger->notify($e, $app->request());
}
```

### Sending Custom Notifications
See the Honeybadger [API documentation](https://docs.honeybadger.io/api/exceptions.html#sample-payload). All fields except the `notifier` are valid.

```php
$honeybadger = Honeybadger::new([
    'api_key' => 'abc123'
]);

$honeybadger->customNotification([
    'title'   => 'Special Error',
    'message' => 'Special Error: a special error has occurred',
]);
```

### Adding Context
```php
$honeybadger->context('foo', 'bar');
```

### Send Application Check-in
```php
$honeybadger = Honeybadger::new([
    'api_key' => 'abc123'
]);

$honeybadger->checkin('1234');
```

## Configuration
Default values are listed below.

```php
[
    // Honeybadger API Key
    'api_key' => null,

    'environment' => [
        // Environment keys to filter before the payload sent to Honeybadger (see Environment Whitelist)
        'filter' => [],

        // Additional environment keys to include (see Environment Whitelist)
        'include' => [],
    ],

    'request' => [
        // Request keys to filter before the payload sent to Honeybadger
        'filter' => [
            'password',
            'password_confirmation'
        ],
    ],

    // Application version
    'version' => '',

    // System hostname
    'hostname' => gethostname(),

    // Project root (/var/www)
    'project_root' => '',

    // Application environment name
    'environment_name' => 'production',

    'handlers' => [
        // Enable global exception handler
        'exception' => true,

        // Enable global error handler
        'error' => true,
    ],
    'client' => [
        // Request timeout length (default: indefinite)
        'timeout' => 0,

        // Request proxy settings
        'proxy' => [
            // Use this proxy with 'http' (tcp://username:password@localhost:8125)
            'http'  => '',

            // Use this proxy with 'https' (tcp://username:password@localhost:8125)
            'https' => '',
        ],
    ],

    // Exclude exceptions from being reported
    'excluded_exceptions' => [],
]
```

### Environment Whitelist
**Note:** All `HTTP_*` keys are included by default.

<details>
    <summary>All whitelisted keys</summary>

```
'PHP_SELF'
'argv'
'argc'
'GATEWAY_INTERFACE'
'SERVER_ADDR'
'SERVER_NAME'
'SERVER_SOFTWARE'
'SERVER_PROTOCOL'
'REQUEST_METHOD'
'REQUEST_TIME'
'REQUEST_TIME_FLOAT'
'QUERY_STRING'
'DOCUMENT_ROOT'
'HTTPS'
'REMOTE_ADDR'
'REMOTE_HOST'
'REMOTE_PORT'
'REMOTE_USER'
'REDIRECT_REMOTE_USER'
'SCRIPT_FILENAME'
'SERVER_ADMIN'
'SERVER_PORT'
'SERVER_SIGNATURE'
'PATH_TRANSLATED'
'SCRIPT_NAME'
'REQUEST_URI'
'PHP_AUTH_DIGEST'
'PHP_AUTH_USER'
'PHP_AUTH_PW'
'AUTH_TYPE'
'PATH_INFO'
'ORIG_PATH_INFO'
'APP_ENV'
```

</details>

## Exceptions
If there is an error contacting Honeybadger a `\Honeybadger\Exceptions\ServiceException::class` will be thrown with a relevant exception message.

## Testing

``` bash
composer test
```

## Code Style
In addition to the php-cs-fixer rules, StyleCI will apply the [Laravel preset](https://docs.styleci.io/presets#laravel).
```bash
composer styles:lint
composer styles:fix
```

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security
If you discover any security related issues, please email oss@tjmiller.co instead of using the issue tracker.

## Credits
- [TJ Miller](https://github.com/sixlive)
- [All Contributors](../../contributors)

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
