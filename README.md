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
We have enabled GitHub integration with [Packagist](https://packagist.org). Packagist is automatically notified when a new release is made on GitHub.

Releases are automated, using [Github Actions](https://github.com/honeybadger-io/honeybadger-php/blob/master/.github/workflows/release.yml):
- When a PR is merged on master, the [run-tests.yml](https://github.com/honeybadger-io/honeybadger-php/blob/master/.github/workflows/run-tests.yml) workflow is executed, which runs the tests.
- If the tests pass, the [release.yml](https://github.com/honeybadger-io/honeybadger-php/blob/master/.github/workflows/release.yml) workflow will be executed.
- Depending on the commit message, a release PR will be created with the suggested the version bump and changelog. Note: Not all commit messages trigger a new release, for example, chore: ... will not trigger a release.
- If the release PR is merged, the release.yml workflow will be executed again, and this time it will create a github release.

## Security
If you discover any security related issues, please email security@honeybadger.io instead of using the issue tracker.

## Credits
- [TJ Miller](https://github.com/sixlive)
- [Ivy Evans](https://github.com/ivy)
- [All Contributors](../../contributors)

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
