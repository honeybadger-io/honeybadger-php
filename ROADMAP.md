# Roadmap

This document outlines the API and tasks required to release v1.0 of the honeybadger client for PHP (the first official release).

## v1.0

- Finalize the public API (may include breaking changes)
- Global exception handler support (we already have these):
  - [`set_exception_handler`](https://secure.php.net/manual/en/function.set-exception-handler.php)
  - [`set_error_handler`](https://secure.php.net/manual/en/function.set-error-handler.php)
- Laravel integration (see [the api reference](http://laravel.com/api/5.0/)).
- Update Slim integration if necessary.
- Update the README to match style of our other client libs (our documentation system slurps these in automatically, and it requires a specific format).
- Add troubleshooting section to README. How can you tell it's working?
- Ensure that all code conforms to [Fig standards](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md).

## Later

- Make `Client` an instance which can be used/configured separately from the global interface. This shouldn't be breaking since the global interface would not change -- it would just delegate to a global client instance.

## Public API

### `Honeybadger::configure`

Configures the global client instance.

#### Examples

```php
Honeybadger::configure(array(
  'api_key' => '[your-api-key]',
));
```

### `Honeybadger::notify`

Sends an exception to Honeybadger.

#### Examples

```php
# Reporting an exception:
try
{
  // ...
}
catch (Exception $e)
{
  Honeybadger::notify($e);
}

# Reporting arbitrary data:
Honeybadger::notify(array(
  'error_class'   => 'Special Error',
  'error_message' => 'An arbitrary error message.',
  'parameters'    => array('key' => 'value'),
));
```

### `Honeybadger::context`

Adds context data which is included when an exception is reported.

#### Examples

```php
Honeybadger::context(array(
  'user_id'    => '1',
  'user_email' => 'user@example.com',
));
```

### `Honeybadger::exception_filter`

Configures a callback which causes notifications to be skipped when it returns `TRUE`. Note: this used to be called `ignore_by_filter`.

#### Examples

```php
Honeybadger::exception_filter(function($notice) {
  if ($notice->error_class == 'Exception') {
    return TRUE;
  }
});
```
