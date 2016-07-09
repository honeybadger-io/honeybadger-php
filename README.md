# honeybadger-php [![Build Status](https://secure.travis-ci.org/honeybadger-io/honeybadger-php.png)](http://travis-ci.org/honeybadger-io/honeybadger-php)

This is the client library for integrating apps with the :zap: [Honeybadger Exception Notifier for PHP](http://honeybadger.io).

When an unhandled exception or error occurs, Honeybadger will POST the relevant data to the Honeybadger server specified in your environment.

## Compatibility

honeybadger-php is developed and tested against PHP versions 5.5 and 5.6.

## Getting Started

1. Add honeybadger-php to your `composer.json`:

    ```javascript
    {
      // ...
      "require": {
        // ...
        "honeybadger-io/honeybadger": "*"
      }
      // ...
    }
    ```

2. Run `composer install`.
3. Complete the installation for your app/framework:

### Standalone Installation

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

### Laravel Installation

Report exceptions to Honeybadger from Laravel's exception handler in *app/start/global.php* (under the "Application Error Handler" heading):

```php
use Honeybadger\Honeybadger;

Honeybadger::$config->values(array(
  'api_key' => '[your-api-key]',
));

App::error(function(Exception $exception, $code)
{
	Honeybadger::notify($exception);
	Log::error($exception);
});
```

See [crywolf-laravel](https://github.com/honeybadger-io/crywolf-laravel) for an example Laravel application.

### Slim Installation

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

## Additional Integrations

This library will work well by following the
[standalone installation](https://github.com/honeybadger-io/honeybadger-php#standalone-installation)
steps outlined above. However, if you want to integrate your favorite framework,
you can use the
[Slim integration](https://github.com/honeybadger-io/honeybadger-php/blob/master/lib/Honeybadger/Slim.php)
as a reference. If you've written your own integration that you'd like to share,
send a [pull request](https://github.com/honeybadger-io/honeybadger-php/pull/new/master)
adding it to the list:

* *Nothing here yet...*

## Sample Application

If you'd like to see the library in action before you integrate it with your apps, check out our [sample application](https://github.com/honeybadger-io/crywolf-laravel). 

You can deploy the sample app to your Heroku account by clicking this button:

[![Deploy](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy?template=https://github.com/honeybadger-io/crywolf-laravel)

Don't forget to destroy the Heroku app after you're done so that you aren't charged for usage.

The code for the sample app is [available on Github](https://github.com/honeybadger-io/crywolf-laravel), in case you'd like to read through it, or run it locally.

## Usage

For the most part, Honeybadger works for itself.

It intercepts unhandled errors and uncaught exceptions and sends notifications.

If you want to log arbitrary things which you've caught yourself, you can do
something like this:

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

The `::notify()` call will send the notice over to Honeybadger for
later analysis.

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

## Sending Custom Data

Honeybadger allows you to send custom data using `Honeybadger::context()`.
Here's an example of sending some user-specific information in a Slim callback:

```php
<?php

$authenticate_user = function() use $app {
  // ...
    if (isset($current_user))
    {
        Honeybadger::context(array(
          'user_id'    => $current_user->id,
          'user_email' => $current_user->email,
        ));
    }
};

// ...

$app->get('/protected_resource', $authenticate_user, function() {
    // ...
});
```

Now whenever an error occurs, Honeybadger will display the affected user's ID
and email address, if available.

Subsequent calls to `::context()` will merge the existing array with any new
data, so you can effectively build up context throughout your request's life
cycle.

## Going Beyond Exceptions

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

`Honeybadger::notify()` will get all the information about the error itself. As
for an array, these are the keys you should pass:

* `error_class` - Use this to group similar errors together. When Honeybadger catches an exception it sends the class name of that exception object.
* `error_message` - This is the title of the error you see in the errors list. For exceptions it is "<exception class> [ <exception code> ] : <exception message>"
* `parameters` - While there are several ways to send additional data to Honeybadger, passing an array as `parameters` as in the example above is the most common use case. When Honeybadger catches an exception in a controller, the actual HTTP client request parameters are sent using this key.

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
);
```

You can override any of those parameters.

### Sending shell environment variables when "Going beyond exceptions"

> One common request we see is to send shell environment variables along with
> manual exception notification.  We recommend sending them along with CGI data
> or Rack environment (:cgi_data or :rack_env keys, respectively.)

See `Honeybadger::Notice::__construct` in
[lib/Honeybadger/Notice.php](https://github.com/honeybadger-io/honeybadger-php/blob/master/lib/Honeybadger/Notice.php)
for more details.

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

## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request

## Credits

This library was originally created by [Gabe Evans](https://github.com/gevans). Thanks Gabe!

## License

MIT Licensed. See [LICENSE](./LICENSE) for details.
