# Change Log
All notable changes to this project will be documented in this file. See [Keep a
CHANGELOG](http://keepachangelog.com/) for how to update this file. This project
adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

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
