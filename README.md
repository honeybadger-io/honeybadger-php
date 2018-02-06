# honeybadger-php [![Build Status](https://secure.travis-ci.org/honeybadger-io/honeybadger-php.png)](http://travis-ci.org/honeybadger-io/honeybadger-php)

This is the client library for integrating apps with the :zap: [Honeybadger Exception Notifier for PHP](http://honeybadger.io).

When an unhandled exception or error occurs, Honeybadger will POST the relevant data to the Honeybadger server specified in your environment.

## Compatibility

honeybadger-php is developed and tested against PHP versions 5.6, 7.0, 7.1, and 7.2.

## Getting Started

### 1. Install the library

First, add honeybadger-php to your `composer.json`:

```javascript
{
    // ...
    "require": {
        // ...
        "honeybadger-io/honeybadger-php": "*"
    }
    // ...
}
```

Then run `composer install`.

### 2. Complete the installation for your app/framework:

#### Standalone Installation

Configure `Honeybadger` in your bootstrap/`index.php`/initializers:

```php
<?php

use Honeybadger\Honeybadger;

Honeybadger::$config->values(array(
    'api_key' => '[your-api-key]',
));
```

Your application will then report unhandled errors and exceptions
to Honeybadger. That's it!


#### Laravel Installation

Add Honeybadger to Laravel's exception handler in *app/exceptions/Handler.php*.  Before the Handler class is opened load Honeybadger:

```php
use Honeybadger\Honeybadger;

Honeybadger::$config->values(array(
    'api_key' => '[your-api-key]',
));

```

And in the *report* function add the code that sends the exception to Honeybadger:

```php
public function report(Exception $exception)
{
    if ($this->shouldReport($exception)) {
        Honeybadger::notify($exception);
    }

    parent::report($exception);
}

```

#### Slim Installation

Call `Honeybadger\Slim::init()` after your application definition:

```php
<?php

$app = new Slim(array(
    // ...
));

$app->add(new Honeybadger\Slim(array(
    'api_key'      => '[your-api-key]',
    'project_root' => realpath(__DIR__),
)));

// ...
$app->run();
```

## Sample Application

If you'd like to see the library in action before you integrate it with your apps, check out our [sample application](https://github.com/honeybadger-io/crywolf-laravel). 

You can deploy the sample app to your Heroku account by clicking this button:

[![Deploy](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy?template=https://github.com/honeybadger-io/crywolf-laravel)

Don't forget to destroy the Heroku app after you're done so that you aren't charged for usage.

The code for the sample app is [available on Github](https://github.com/honeybadger-io/crywolf-laravel), in case you'd like to read through it, or run it locally.

## Configuration

To set configuration options, update the `Honeybadger::$config` array, like so:

```php
<?php

Honeybadger::$config->values(array(
    'api_key'          => '[your-api-key]',
    'environment_name' => 'production',
));

```
The following options are available to you:

|  Name | Type | Description |
| ----- | ---- | ----------- |
| api_key | `string` | The API key for your project, found on the project edit form. |
| host | `string` | The host to connect to (defaults to `api.honeybadger.io`). |
| port | `integer` | The port on which your Honeybadger server runs (defaults to `443` for secure connections, `80` for insecure connections).  |
| http_open_timeout | `integer` | The HTTP open timeout in seconds (defaults to `2`). |
| http_read_timeout | `integer` | The HTTP read timeout in seconds (defaults to `5`). |
| proxy_host | `string` | The hostname of your proxy server (if using a proxy). |
| proxy_port | `integer` | The port of your proxy server (if using a proxy). |
| proxy_user | `string` | The username to use when logging into your proxy server (if using a proxy). |
| proxy_pass | `string` | The password to use when logging into your proxy server (if using a proxy). |
| backtrace_filters | `array` | A list of filters for cleaning and pruning the backtrace. See `Config::filter_backtrace`. |
| params_filters | `array` | A list of parameters that should be filtered out of what is sent to Honeybadger. By default, all `password` and `password_confirmation` attributes will have their contents replaced. |
| ignore_by_filters | `array` | A list of filters for ignoring exceptions. See `Config::ignore_by_filter`. |
| ignore | `array` | A list of exception classes to ignore. |
| ignore_user_agents | `array` | A list of user agents to ignore. |
| development_environments | `array` | A list of environments in which notifications should not be sent. |
| environment_name | `string` | The name of the environment the application is running in. |
| project_root | `string` | The path to the project in which the error occurred. |
| user_information | `string` | The text that the placeholder is replaced with. `{{error_id}}` is the actual error number. |
| source_extract_radius | `integer` | The radius around trace line to include in source excerpt. |
| send_request_session | `boolean` | `true` to send session data, `false` to exclude. |
| debug | `boolean` | `true` to log extra debug info, `false` to suppress. |

## Public Interface

### `Honeybadger::notify()`: Send an error to Honeybadger.

If you've caught an exception in your code, but would still like to report the error to Honeybadger, this is the method for you. 

#### Examples:

```php
<?php

try
{
    // ...
}
catch (Exception $e)
{
    Honeybadger::notify($e);
}
// ...
```

You can also pass an array to the `Honeybadger::notify()` method and store
whatever you want, not just an exception, anywhere in your app.

```php
<?php

try
{
    $params = array(
        // Params that you pass to a method that can throw an exception.
    );
    my_unpredictable_method($params);
}
catch (Exception $e)
{
    Honeybadger::notify(array(
        'error_class'   => 'Special Error',
        'error_message' => 'Special Error: '.$e->getMessage(),
        'parameters'    => $params,
    ));
}
```

`Honeybadger::notify()` will get all the information about the error itself. These are the keys you can provide to the array:

| Key | Description | Type |
| --- | ----------- | ---- |
| `api_key` | The API key used by Honeybadger to locate your project. | `String` |
| `context` | Any custom or arbitrary data should be sent in the context array. Local context is automatically merged with global context set via `Honeybadger::context()` when reporting the error. | `Array` |
| `cgi_data` | Used for server environment variables. | `Array` |
| `error_class` | Use this to group similar errors together. When Honeybadger catches an exception it sends the class name of that exception object. | `String` |
| `error_message` | This is the title of the error you see in the errors list. For exceptions it is "\<exception class\> [ \<exception code\> ] : \<exception message\>" | `String` |
| `parameters` | When Honeybadger catches an exception in a controller, the actual HTTP client request parameters are sent using this key. | `Array` |
| `session` | The current session, for web requests. | `Array` |
| `backtrace` | A PHP backtrace pointing to the location in the code which caused the error. | `Array` |

Honeybadger merges the array you pass with these default options:

```php
<?php

array(
    'api_key'       => Honeybadger::$config->api_key,
    'error_message' => 'Notification',
    'backtrace'     => debug_backtrace(),
    'parameters'    => array(),
    'session'       => $_SESSION,
    'context'       => Honeybadger::context(),
    'cgi_data'    => array(),
);
```

You can override any of the default options.

### Sending shell environment variables

> One common request we see is to send shell environment variables along with
> manual exception notification. We recommend sending them along with CGI data
> (:cgi_data key).

See `Honeybadger::Notice::__construct` in
[lib/Honeybadger/Notice.php](https://github.com/honeybadger-io/honeybadger-php/blob/master/lib/Honeybadger/Notice.php)
for more details.

---

### `Honeybadger::context()`: Set metadata to be sent if an error occurs

This method lets you set context data that will be sent if an error should occur.

For example, it's often useful to record the current user's ID and/or email address when an error occurs in a web app. To do that, just use `::context` to set the user info on each request. If an error occurs, the id will be reported with it.

#### Examples:

```php
<?php

Honeybadger::context(array(
    'user_id'    => 1,
    'user_email' => 'user@example.com',
));
```

Now whenever an error occurs, Honeybadger will display the affected user's ID
and email address, if available.

Subsequent calls to `::context()` will merge the existing array with any new
data, so you can effectively build up context throughout your request's life
cycle.

---

### `Honeybadger::resetContext()`: Clear context metadata

If you've used `Honeybadger::context()` to store context data, you can clear it with `Honeybadger::resetContext()`.

#### Example:

```php
<?php

Honeybadger::resetContext();
```

You can also pass an array as the first argument to replace the context data:

```php
<?php

Honeybadger::resetContext(array(
    'user_email' => 'user@example.com'
));
```

---

## Ignored Environments

Please note that in development mode, Honeybadger will **not** be notified of
exceptions that occur. In production, make sure you sure you set the environment
name for Honeybadger. For apps using the Slim integration, Honeybadger will
handle this for you by using [your app's configured mode](http://docs.slimframework.com/#Application-Settings):

```php
<?php

Honeybadger::$config->values(array(
    // ...
    'environment_name' => 'production',
));
```

You can modify which environments are ignored by setting the
`development_environments` option in your Honeybadger initializer:

```php
<?php

// To add an additional environment to be ignored:
Honeybadger::$config->development_environments[] = 'staging';

// To override the default environments completely:
Honeybadger::$config->development_environments = array(
    'test', 'behat',
);
```

If you choose to override the `development_environments` option for whatever
reason, please make sure your test environments are ignored.

## Filtering

You can specify a whitelist of errors that Honeybadger will not report on. Use
this feature when you are so apathetic to certain errors that you don't want
them even logged.

This filter will only be applied to automatic notifications, not manual
notifications (when `::notify()` is called directly).

To ignore errors, specify their names in your Honeybadger configuration block:

```php
<?php

Honeybadger::$config->ignore[] = 'SomeException';
```

To ignore *only* certain errors (and override the defaults), use the `ignore_only()` method:

```php
<?php

Honeybadger::$config->ignore_only('RandomError');
```

Subclasses of ignored classes will also be ignored.

To ignore certain user agents, add in the `ignore_user_agent` attribute:

```php
<?php

Honeybadger::$config->ignore_user_agent[] = 'IgnoredUserAgent';
```

To ignore exceptions based on other conditions, use `ignore_by_filter`:

```php
<?php

Honeybadger::$config->ignore_by_filter(function($notice) {
    if ($notice->error_class == 'Exception')
        return true;
});
```

To replace sensitive information sent to the Honeybadger service with
`[FILTERED]` use `params_filters`:

```php
<?php

Honeybadger::$config->params_filters[] = 'credit_card_number';
```

To disable sending session data:

```php
Honeybadger::$config->values(array(
    'api_key' => '1234567890abcdef',
    'send_request_session' => false,
));
```

## Proxy Support

The notifier supports using a proxy, if your server is not able to
directly reach the Honeybadger servers. To configure the proxy settings,
add the following information to your Honeybadger configuration.

```php
<?php

Honeybadger::$config->values(array(
    'proxy_host' => 'proxy.host.com',
    'proxy_port' => 4038,
    'proxy_user' => 'foo', // optional
    'proxy_pass' => 'bar', // optional
));
```

## Changelog

See https://github.com/honeybadger-io/honeybadger-php/blob/master/CHANGELOG.md

## Contributing

If you're adding a new feature, please [submit an issue](https://github.com/honeybadger-io/honeybadger-php/issues/new) as a preliminary step; that way you can be (moderately) sure that your pull request will be accepted.

### To contribute your code:

1. Fork it.
2. Create a topic branch `git checkout -b my_branch`
3. Commit your changes `git commit -am "Boom"`
3. Push to your branch `git push origin my_branch`
4. Send a [pull request](https://github.com/honeybadger-io/honeybadger-php/pulls)

## Credits

This library was originally created by [Gabe Evans](https://github.com/gevans). Thanks Gabe!

### License

This library is MIT licensed. See the [LICENSE](https://raw.github.com/honeybadger-io/honeybadger-php/master/LICENSE) file in this repository for details.
