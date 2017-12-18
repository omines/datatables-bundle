# Changelog
All notable changes to `omines\datatables-bundle` will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added
 - Add BoolColumn for handling strict boolean columns

### Changed
 - Column values default to 'data' only on NULL instead of any 'emptiness'

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

[Unreleased]: https://github.com/omines/datatables-bundle/compare/0.1.2...master
[0.1.2]: https://github.com/omines/datatables-bundle/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/omines/datatables-bundle/compare/0.1.0...0.1.1
