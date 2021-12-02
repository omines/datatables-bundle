# Changelog
All notable changes to `omines\datatables-bundle` will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
Nothing yet.

## [0.5.2] - 2021-01-07
### Fixed
 - Fix depreciations (#198)
 - Fix spurious deprecation warnings in Symfony

## [0.5.1] - 2020-08-25
### Fixed
 - Fix dependency issue

## [0.5.0] - 2020-07-03
### Added
 - Support for server-side exports (#83)
 - Support for per-column searches (#120)
 - ORM adapter supporting fetch joins (#121)
 - TwigStringColumn type for simple inline templating (#146)

### Changed
 - Drop Symfony <4.4 compatibility

### Fixed
 - Use trans() for proper locale fallback when using CDN languages (#141)

## [0.4.2] - 2020-04-08
### Added
 - Update translations automatically with script (#130)
 - Add support for closures in frontend JS code (#145)
 
### Fixed
 - Fixed deprecation warnings (#129)

## [0.4.1] - 2020-02-21
### Added
 - Implement basic support for embeddables (#86)
 - Option for custom datetime format for creating object (#127)

### Fixed
 - Fixed issue with unitialized datatable (#40)
 - Fixed some autowiring issues surrounding contracts (#122)

## [0.4.0] - 2019-12-23
### Changed
 - Make compatible with Doctrine Bundle 2.0
 - Make compatible with Symfony 5.0
 - Drop Symfony 3.x compatibility
 - Drop PHP <7.2 compatibility

### Deprecated
 - DataTablesTrait should be dropped in favor of injection

## [0.3.1] - 2019-08-09
### Added
 - Update the url used for ajax request on each init. (#75)

### Fixed
 - Fix array filtering (#88)

## [0.3.0] - 2019-05-14
### Added
 - Add DataTable events (#76)

### Fixed
 - Fix double transformations in ArrayAdapter (#70)

## [0.2.2] - 2019-02-25
### Added
 - Add ability to join from inverse side (#63)
 
### Changed
 - Drop unsupported Symfony versions for dependencies and tests
 
### Fixed
 - Fix ORMAdapter not correctly parsing GroupBy DQL parts
 - Fix deprecation warnings resulting from Symfony 4.1/4.2

## [0.2.1] - 2018-11-29
### Changed
 - Update German translations
 - Switch to PHPunit 6.x/7.x

### Fixed
 - Fix hydrationMode=Query::HYDRATE_ARRAY (#36)
 - Fix global search for numbers and booleans
 
## [0.2.0] - 2018-05-23
### Added
 - Add ElasticaAdapter for use with ruflin/elastica
 - Add MapColumn for rendering enumerated types from a predefined list of options

### Changed
 - Moved internal DI config to XML so Yaml dependency can be dropped
 - Dropped direct requirement of twig/twig package

## [0.1.5] - 2018-01-25
### Fixed
 - Fixed inconsistency in DateTimeColumn with default/null values

## [0.1.4] - 2018-01-21
### Added
 - Add TwigColumn for easily rendering Twig templates into your table cells
 - Column types can now be declared as services and have dependencies injected
 
### Changed
 - Moved AbstractColumn initialization from constructor to dedicated function
   to facilitate being instantiated as services.

## [0.1.3] - 2017-12-18
### Added
 - Add BoolColumn for handling strict boolean columns

### Changed
 - Column values default to 'data' only on NULL instead of any 'emptiness'

### Fixed
 - Moved public assets back into bundle's public folder

## [0.1.2] - 2017-12-14
### Added
 - Implement persist_state parameter to automate table state persistence

### Fixed
 - Fixed exception during template rendering when enabling searching serverside
 - Fixed sort behavior when defaulting field detection

## [0.1.1] - 2017-12-03
### Fixed
 - Changed ORMAdapter autowire to manual to avoid compile time failures when optional
   dependencies are missing

## 0.1.0 - 2017-12-01
### Added
 - Basic functionality

[Unreleased]: https://github.com/omines/datatables-bundle/compare/0.5.2...master
[0.5.2]: https://github.com/omines/datatables-bundle/compare/0.5.1...0.5.2
[0.5.1]: https://github.com/omines/datatables-bundle/compare/0.5.0...0.5.1
[0.5.0]: https://github.com/omines/datatables-bundle/compare/0.4.2...0.5.0
[0.4.2]: https://github.com/omines/datatables-bundle/compare/0.4.1...0.4.2
[0.4.1]: https://github.com/omines/datatables-bundle/compare/0.4.0...0.4.1
[0.4.0]: https://github.com/omines/datatables-bundle/compare/0.3.1...0.4.0
[0.3.1]: https://github.com/omines/datatables-bundle/compare/0.3.0...0.3.1
[0.3.0]: https://github.com/omines/datatables-bundle/compare/0.2.2...0.3.0
[0.2.2]: https://github.com/omines/datatables-bundle/compare/0.2.1...0.2.2
[0.2.1]: https://github.com/omines/datatables-bundle/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/omines/datatables-bundle/compare/0.1.5...0.2.0
[0.1.5]: https://github.com/omines/datatables-bundle/compare/0.1.4...0.1.5
[0.1.4]: https://github.com/omines/datatables-bundle/compare/0.1.3...0.1.4
[0.1.3]: https://github.com/omines/datatables-bundle/compare/0.1.2...0.1.3
[0.1.2]: https://github.com/omines/datatables-bundle/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/omines/datatables-bundle/compare/0.1.0...0.1.1
