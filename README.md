# Honeybadger PHP library

[![Latest Version on Packagist](https://img.shields.io/packagist/v/honeybadger-io/honeybadger-php.svg?style=flat-square)](https://packagist.org/packages/honeybadger-io/honeybadger-php)
[![Total Downloads](https://img.shields.io/packagist/dt/honeybadger-io/honeybadger-php.svg?style=flat-square)](https://packagist.org/packages/honeybadger-io/honeybadger-php)
![Run Tests](https://github.com/honeybadger-io/honeybadger-php/workflows/Run%20Tests/badge.svg)
[![StyleCI](https://styleci.io/repos/9077424/shield)](https://github.styleci.io/repos/9077424)

This is the client library for integrating apps with the :zap: [Honeybadger Exception Notifier for PHP](https://www.honeybadger.io/for/php/?utm_source=github&utm_medium=readme&utm_campaign=php&utm_content=Honeybadger+Exception+Notifier+for+PHP).

## Framework Integrations

* Laravel - [honeybadger-io/honeybadger-laravel](https://github.com/honeybadger-io/honeybadger-laravel)

## Documentation and Support

For comprehensive documentation and support, [check out our documentation site](https://docs.honeybadger.io/lib/php/index.html).

## Testing

``` bash
composer test
```

## Code Style
This project follows the [PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md). In addition, StyleCI will apply the [Laravel preset](https://docs.styleci.io/presets#laravel).

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Releasing
We have enabled GitHub integration with [Packagist](https://packagist.org). Packagist is automatically notified when a new release is made on GitHub:
1. Make sure `CHANGELOG.md` is updated
2. Create a new release on the GitHub UI:
  - Create a new tag (i.e. `v3.15.1`)
  - Set release title as the version (i.e. `3.15.1`)
  - Copy/paste changelog into release description

## Security
If you discover any security related issues, please email security@honeybadger.io instead of using the issue tracker.

## Credits
- [TJ Miller](https://github.com/sixlive)
- [Ivy Evans](https://github.com/ivy)
- [All Contributors](../../contributors)

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
