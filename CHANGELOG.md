# Change Log
All notable changes to this project will be documented in this file. See [Keep a
CHANGELOG](http://keepachangelog.com/) for how to update this file. This project
adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [2.7.0] - 2021-08-09
### Added
- Added support for array parameters and chaining in `context()` method ([#136](https://github.com/honeybadger-io/honeybadger-php/pull/136))

### Fixed
- Send empty context as JSON object, not array ([#138](https://github.com/honeybadger-io/honeybadger-php/pull/138))
- Serialise objects in backtrace arguments as literals, not strings ([#133](https://github.com/honeybadger-io/honeybadger-php/pull/133))

## [2.6.0] - 2021-02-24
### Fixed
- The size of each backtrace argument is now limited to nesting depth of 10 and 50 array keys ([#134](https://github.com/honeybadger-io/honeybadger-php/pull/134)).

## [2.5.0] - 2021-02-19
### Added
- Added `service_exception_handler` config item to allow users configure how ServiceExceptions should be handled ([#129](https://github.com/honeybadger-io/honeybadger-php/pull/129))

### Fixed
- `vendor_paths` on Windows are now matched correctly. ([128](https://github.com/honeybadger-io/honeybadger-php/pull/128))

## [2.4.1] - 2021-02-15
### Fixed
- Fixed default value for upgrading older installations ([#126](https://github.com/honeybadger-io/honeybadger-php/pull/126))

## [2.4.0] - 2021-02-15
### Added
- Added config for Guzzle SSL verification ([#124](https://github.com/honeybadger-io/honeybadger-php/pull/124)) ([#123](https://github.com/honeybadger-io/honeybadger-php/pull/123))

## [2.3.0] - 2020-11-29
### Changed
- Added PHP8 Support ([#118](https://github.com/honeybadger-io/honeybadger-php/pull/118))

## [2.2.2] - 2020-11-6
### Fixed
- Fixed an issue filtering keyed arrays ([#117](https://github.com/honeybadger-io/honeybadger-php/pull/117))

## [2.2.1] - 2020-09-14
- Changed the seprator for flex version dependencies in the composer file. Might be causing an issue ([#115](https://github.com/honeybadger-io/honeybadger-php/pull/115))
- Updated the mimimum version of Guzzle to `7.0.1` ([#115](https://github.com/honeybadger-io/honeybadger-php/pull/115))

## [2.2.0] - 2020-09-08
## Added
- Backtrace context for app/vendor files for filtering in HB UI ([#114](https://github.com/honeybadger-io/honeybadger-php/pull/114))
- Environment context for raw and custom notifications ([#113](https://github.com/honeybadger-io/honeybadger-php/pull/113))

## [2.1.0] - 2020-02-10
### Changed
- Improved log reporter payload ([#106](https://github.com/honeybadger-io/honeybadger-php/pull/106))

## [2.0.2] - 2020-02-10
### Fixed
- Fixed an issue with error reporting ([#104](https://github.com/honeybadger-io/honeybadger-php/pull/104))

### Changed
- Added array to doc block for context ([#103](https://github.com/honeybadger-io/honeybadger-php/pull/103))

## [2.0.1] - 2019-11-18
### Fixed
- Fixed an issue where a payload containing recursive data couldn't be posted to the backend ([#96](https://github.com/honeybadger-io/honeybadger-php/pull/96))
- Fixed an issue where the previous exception handler is not callable but called ([#97](https://github.com/honeybadger-io/honeybadger-php/pull/97))

## [2.0.0] - 2019-09-21
### Changed
- Updated Monolog dependency to 2.0
- Remove support for PHP 7.1

## [1.7.1] - 2019-09-13
### Fixed
- Default args for backtrace functions ([#92](https://github.com/honeybadger-io/honeybadger-php/pull/92))

## [1.7.0] - 2019-09-04
### Added
- Methods to set the component and action ([#87](https://github.com/honeybadger-io/honeybadger-php/pull/87))
- Class and type to backtrace frames ([#72](https://github.com/honeybadger-io/honeybadger-php/pull/72/))

### Changed
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
