# Change Log
All notable changes to this project will be documented in this file. See [Keep a
CHANGELOG](http://keepachangelog.com/) for how to update this file. This project
adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
## Changed
- Backtrace args with objects now send only the class name ([#89](https://github.com/honeybadger-io/honeybadger-php/pull/89))

## [1.6.0] - 2019-07-18
### Added
- Added the ability to pass additional API parameters to exception captures specifically component and action ([#85](https://github.com/honeybadger-io/honeybadger-php/pull/85))
- Adds fingerprint and tags to the additional paramaters ([#76](https://github.com/honeybadger-io/honeybadger-php/pull/76))
- Adds method arguments to backtrace where possible ([#86](https://github.com/honeybadger-io/honeybadger-php/pull/86))

## [1.5.1] - 2019-06-10
### Fixed
* Error handler reporting supressed errors ([#83](https://github.com/honeybadger-io/honeybadger-php/pull/83))

## [1.5.0] 2019-05-30

### Added
* New option for whether the library should send notifications back to the Honeybadger API ([#82](https://github.com/honeybadger-io/honeybadger-php/pull/82))

## [1.4.0] 2019-04-17

### Added
* Fully customizable notification method ([#70](https://github.com/honeybadger-io/honeybadger-php/pull/70))
* Ability to reset context ([#71](https://github.com/honeybadger-io/honeybadger-php/pull/71))
* Monolog Handler ([#70](https://github.com/honeybadger-io/honeybadger-php/pull/70))
* PHPUnit 8 support ([#79](https://github.com/honeybadger-io/honeybadger-php/pull/79))

## Fixed
* Empty `api_key` value ([#80](https://github.com/honeybadger-io/honeybadger-php/pull/80))

## [1.3.0] 2018-12-17
### Added
* PHP 7.3 to the Travis build matrix ([#68](https://github.com/honeybadger-io/honeybadger-php/pull/68))

### Removed
* php-cs-fixer dev dependency ([#69](https://github.com/honeybadger-io/honeybadger-php/pull/69))

## [1.2.1] 2018-11-08
### Fixed
- Fixed an issue with merging a custom notifier from the config ([#67](https://github.com/honeybadger-io/honeybadger-php/pull/67))

## [1.2.0] - 2018-09-13
### Changed
- Lowered required version of `symfony/http-foundation` ([#65](https://github.com/honeybadger-io/honeybadger-php/pull/65))

## [1.1.0] - 2018-08-17
### Changed
- Allow `null` value for `api_key` config to improve local project development.

## [1.0.0] - 2018-07-07
### Changed
- Full library rewrite
- PHP 7.1|7.2 requirement
- See [README](README.md) for new installation, usage, and configuration details

## [0.4.1] - 2018-06-12
## Fixed
- PHP 5.5 support (#54)
- Fixes port duplication in URL (#53)

## [0.4.0] - 2018-04-08
### Added
- Adds the ability to disable and restore error and exception handlers (#50)
- Adds the ability to filter reported keys (#48)

## [0.3.2] - 2018-03-11
### Fixed
- Fixes a bug in proxy URL configuration based on settings

## [0.3.1] - 2016-10-31
### Fixed
- Fix a bug where `$config` was not initialized until calling
  `Honeybadger::init()`.

## [0.3.0] - 2016-08-29
### Added
- Updated fig standards
- Adding official support

## [0.2.0] - 2015-06-22
### Fixed
- Fixes inefficiency in notice building - #1
- Fixes missing breaks in Slim logger - #3
- Fixes package name in documentation - #4

## [0.1.0] - 2013-04-05
### Added
- Initial release, -Gabriel Evans
