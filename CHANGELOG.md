# Change Log
All notable changes to this project will be documented in this file. See [Keep a
CHANGELOG](http://keepachangelog.com/) for how to update this file. This project
adheres to [Semantic Versioning](http://semver.org/).

## [2.25.3](https://github.com/honeybadger-io/honeybadger-php/compare/v2.25.2...v2.25.3) (2026-02-24)


### Miscellaneous Chores

* add support for Laravel 13.x ([#240](https://github.com/honeybadger-io/honeybadger-php/issues/240)) ([c7c1c59](https://github.com/honeybadger-io/honeybadger-php/commit/c7c1c59a53d865cfbdfe954d33843529b88a50a2))
* fix PSR-2 brace placement and elseif style violations ([#241](https://github.com/honeybadger-io/honeybadger-php/issues/241)) ([74916cb](https://github.com/honeybadger-io/honeybadger-php/commit/74916cbd6e7a91b115229a4d89e1993f2660da9b))

## [2.25.2](https://github.com/honeybadger-io/honeybadger-php/compare/v2.25.1...v2.25.2) (2026-02-18)


### Bug Fixes

* getContentType still exists in symfony 6.2, therefore, it should prioritize recommended method ([#237](https://github.com/honeybadger-io/honeybadger-php/issues/237)) ([6d28da9](https://github.com/honeybadger-io/honeybadger-php/commit/6d28da92e4738ea13a8cc2786532e330ac74a014))

## [2.25.1](https://github.com/honeybadger-io/honeybadger-php/compare/v2.25.0...v2.25.1) (2026-02-01)


### Bug Fixes

* use literal 2048 for E_STRICT to avoid PHP 8.4 deprecation (fixes [#234](https://github.com/honeybadger-io/honeybadger-php/issues/234)) ([#235](https://github.com/honeybadger-io/honeybadger-php/issues/235)) ([c044803](https://github.com/honeybadger-io/honeybadger-php/commit/c04480366aafe5791a7f79ccc196281b2ffdefbc))

## [2.25.0](https://github.com/honeybadger-io/honeybadger-php/compare/v2.24.1...v2.25.0) (2025-09-03)


### Features

* event context ([#232](https://github.com/honeybadger-io/honeybadger-php/issues/232)) ([0bdd49d](https://github.com/honeybadger-io/honeybadger-php/commit/0bdd49d43f11d28aca3f2df12508c190e153372b)), closes [#231](https://github.com/honeybadger-io/honeybadger-php/issues/231)


### Miscellaneous Chores

* release if needed from workflow_dispatch ([26c4d85](https://github.com/honeybadger-io/honeybadger-php/commit/26c4d85b7a5e63423d77b830e3aa823367aefc14))

## [2.24.1](https://github.com/honeybadger-io/honeybadger-php/compare/v2.24.0...v2.24.1) (2025-06-02)


### Performance Improvements

* increase defaults for events dispatcher ([#229](https://github.com/honeybadger-io/honeybadger-php/issues/229)) ([11ec9d3](https://github.com/honeybadger-io/honeybadger-php/commit/11ec9d3e02dfcb6574a1d465b3a625e74974a9b8))

## [2.24.0](https://github.com/honeybadger-io/honeybadger-php/compare/v2.23.0...v2.24.0) (2025-05-02)


### Features

* add Insights event sampling ([#227](https://github.com/honeybadger-io/honeybadger-php/issues/227)) ([bb506c8](https://github.com/honeybadger-io/honeybadger-php/commit/bb506c89755d0b83f1f7451c7dae6fa5eeff7ba5))

## [2.23.0](https://github.com/honeybadger-io/honeybadger-php/compare/v2.22.1...v2.23.0) (2025-01-07)


### Features

* add support for beforeNotify and beforeEvent handlers ([#222](https://github.com/honeybadger-io/honeybadger-php/issues/222)) ([534634c](https://github.com/honeybadger-io/honeybadger-php/commit/534634c66bd22f0ddfa11d535c4f958aa898d0de))


### Bug Fixes

* **checkins:** read error message from response body ([#224](https://github.com/honeybadger-io/honeybadger-php/issues/224)) ([f6fd084](https://github.com/honeybadger-io/honeybadger-php/commit/f6fd084522a371b992d9544dfd36469eff43689e))

## [2.22.1](https://github.com/honeybadger-io/honeybadger-php/compare/v2.22.0...v2.22.1) (2024-12-09)


### Performance Improvements

* add PHP 8.4 to test matrix ([#218](https://github.com/honeybadger-io/honeybadger-php/issues/218)) ([de5747f](https://github.com/honeybadger-io/honeybadger-php/commit/de5747f3c1f818c1caf6750cd103f376ed442204))

## [2.22.0](https://github.com/honeybadger-io/honeybadger-php/compare/v2.21.0...v2.22.0) (2024-11-16)


### Features

* ignore breadcrumbs with empty message ([#215](https://github.com/honeybadger-io/honeybadger-php/issues/215)) ([645a672](https://github.com/honeybadger-io/honeybadger-php/commit/645a672f26ce5a249017d3e79405bcb3f6ee45e5))

## [2.21.0](https://github.com/honeybadger-io/honeybadger-php/compare/v2.20.0...v2.21.0) (2024-11-01)


### Features

* add events api exception message ([#213](https://github.com/honeybadger-io/honeybadger-php/issues/213)) ([e0a0b90](https://github.com/honeybadger-io/honeybadger-php/commit/e0a0b90c6a1acfc188be3cabaf7907d448824bba))
* send user agent in http clients ([#207](https://github.com/honeybadger-io/honeybadger-php/issues/207)) ([3bd7466](https://github.com/honeybadger-io/honeybadger-php/commit/3bd7466f258711de43676db5240c05129926fe43))


### Bug Fixes

* add noop handler for events exceptions ([#208](https://github.com/honeybadger-io/honeybadger-php/issues/208)) ([3eb1947](https://github.com/honeybadger-io/honeybadger-php/commit/3eb1947f5f0f1f7f49782d9020f7a75964fbb0cb))

## [2.20.0](https://github.com/honeybadger-io/honeybadger-php/compare/v2.19.5...v2.20.0) (2024-10-24)


### Features

* make endpoint app url configurable ([#204](https://github.com/honeybadger-io/honeybadger-php/issues/204)) ([f6f5dcf](https://github.com/honeybadger-io/honeybadger-php/commit/f6f5dcfea3076239a673b13b012ce24f047e7e81))

## [2.19.5](https://github.com/honeybadger-io/honeybadger-php/compare/v2.19.4...v2.19.5) (2024-10-11)


### Bug Fixes

* use RFC3339 extended date format ([#200](https://github.com/honeybadger-io/honeybadger-php/issues/200)) ([1cf4ba3](https://github.com/honeybadger-io/honeybadger-php/commit/1cf4ba3f8fb29e78f929633e298faf2ab3b68409))

## [2.19.4](https://github.com/honeybadger-io/honeybadger-php/compare/v2.19.3...v2.19.4) (2024-08-28)


### Bug Fixes

* check if api key is set in Honeybadger::event ([#198](https://github.com/honeybadger-io/honeybadger-php/issues/198)) ([9fb1390](https://github.com/honeybadger-io/honeybadger-php/commit/9fb13908b7d2f53a0c902a8ab66afdc239afba7f))

## [2.19.3](https://github.com/honeybadger-io/honeybadger-php/compare/v2.19.2...v2.19.3) (2024-07-06)


### Bug Fixes

* revert to deprecated version of logger levels for wider support ([#196](https://github.com/honeybadger-io/honeybadger-php/issues/196)) ([4ab5c01](https://github.com/honeybadger-io/honeybadger-php/commit/4ab5c01c790a6d1cc06d9b8ebeea35d33d2e3fca))


### Miscellaneous Chores

* minor typo fix ([a3df488](https://github.com/honeybadger-io/honeybadger-php/commit/a3df48835266f21f5ed5ab16f5d2bf6b96f39fdc))

## [2.19.2] - 2024-07-06
### Fixed
- Events: Honeybadger.flushEvents() should check if events are enabled before sending to Honeybadger
### Changed
- Events: Register shutdown handler by default

## [2.19.1] - 2024-06-29
### Fixed
- Events: Allow Honeybadger.event() without event_type

## [2.19.0] - 2024-06-28
### Added
- Events: Honeybadger.event() method to send custom events to Honeybadger
- Events: Monolog logger to send logs as events to Honeybadger

## [2.18.0] - 2023-12-28
### Changed
- Check-Ins: Remove project_id from configuration API

## [2.17.3] - 2023-12-04
### Fixed
- Use $request->getContentTypeFormat

## [2.17.2] - 2023-11-16
### Refactored
- Check-Ins: checkins to check-ins

## [2.17.1] - 2023-11-15
### Fixed
- Check-Ins: Do not allow check-ins with same names and project id
- Check-Ins: Send empty string for optional values so that they will be updated when unset

## [2.17.0] - 2023-10-27
### Added
- Check-Ins: Support for slug configuration within the package

## [2.16.0] - 2023-10-17
### Added
- Allow calling the checkin method with a checkin id or name

## [2.15.0] - 2023-10-06
### Added
- Support for checkins configuration within the package

## [2.14.1] - 2023-08-06
### Fixed
- LogHandler: Check log level before writing the log [#168](https://github.com/honeybadger-io/honeybadger-php/pull/168)

## [2.14.0] - 2023-02-16
- Monolog 3 support

## [2.13.0] - 2022-09-08
### Modified
- Remove spatie/regex dependency ([#165](https://github.com/honeybadger-io/honeybadger-php/pull/165))

## [2.12.1] - 2022-05-18
### Added
- Fix occasionally missing backtrace in errors (closes #162) ([341aefc](https://github.com/honeybadger-io/honeybadger-php/commit/341aefc3bb17c7beb71e81b4d7f918125f69c8ff))

## [2.12.0] - 2022-05-06
### Added
- Format errors differently from exceptions ([#160](https://github.com/honeybadger-io/honeybadger-php/pull/160))
- `capture_deprecations` option to disable capturing deprecation warnings ([#160](https://github.com/honeybadger-io/honeybadger-php/pull/160))

## [2.11.3] - 2022-02-07
### Fixed
- Fix deprecation warning for type mismatch on PHP 8.1 ([#158](https://github.com/honeybadger-io/honeybadger-php/pull/158))

## [2.11.2] - 2021-11-30
### Fixed
- Remove unnecessary environment variables ([#155](https://github.com/honeybadger-io/honeybadger-php/pull/155))
- PHP 8.1 fixes ([092b1bb7e8b2733fad40d6724a59e4370391a8e9](https://github.com/honeybadger-io/honeybadger-php/commit/092b1bb7e8b2733fad40d6724a59e4370391a8e9)))

## [2.11.1] - 2021-11-03
### Fixed
- Filter configured query parameters from the URL ([#154](https://github.com/honeybadger-io/honeybadger-php/pull/154))

## [2.11.0] - 2021-06-29
### Added
- Improve how Monolog messages are formatted/displayed ([#152](https://github.com/honeybadger-io/honeybadger-php/pull/152))

## [2.10.0] - 2021-06-16
### Added
- Add endpoint config option ([#150](https://github.com/honeybadger-io/honeybadger-php/pull/150))
- Set default client timeout ([#151](https://github.com/honeybadger-io/honeybadger-php/pull/151))

## [2.9.2] - 2021-05-10
### Fixed
- Handle silenced errors properly on PHP 8 ([#149](https://github.com/honeybadger-io/honeybadger-php/pull/149))

## [2.9.1] - 2021-05-10
### Fixed
- Allow for spatie/regex v2 ([#148](https://github.com/honeybadger-io/honeybadger-php/pull/148))

## [2.9.0] - 2021-05-09
### Modified
- Make internal methods of client `protected` ([953c7b258bc75d33a7045c6005ed0a5eda3f9a11](https://github.com/honeybadger-io/honeybadger-php/commit/953c7b258bc75d33a7045c6005ed0a5eda3f9a11))

## [2.8.3] - 2021-04-13
### Fixed
- Fix return type signature of `get()` method ([e340a6e7a16fa9f452bd2eb6731f7d488e85ef11](https://github.com/honeybadger-io/honeybadger-php/commit/e340a6e7a16fa9f452bd2eb6731f7d488e85ef11))

## [2.8.2] - 2021-04-12
### Added
- Added `get()` method to config repository ([3800e78518618779279742cc375b1f61943f1dcc](https://github.com/honeybadger-io/honeybadger-php/commit/3800e78518618779279742cc375b1f61943f1dcc))

## [2.8.1] - 2021-03-25
### Fixed
- Capture the previous exception when throwing generic ServiceException ([#143](https://github.com/honeybadger-io/honeybadger-php/pull/143))

## [2.8.0] - 2021-03-16
### Added
- Added support for collecting breadcrumbs ([#141](https://github.com/honeybadger-io/honeybadger-php/pull/141))

## [2.7.0] - 2021-03-09
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
- Adds fingerprint and tags to the additional parameters ([#76](https://github.com/honeybadger-io/honeybadger-php/pull/76))
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
