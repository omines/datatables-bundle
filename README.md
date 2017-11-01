# Symfony DataTables Bundle
[![Latest Version](https://img.shields.io/github/release/omines/datatables-bundle.svg?style=flat-square)](https://github.com/omines/datatables-bundle/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/omines/datatables-bundle/master.svg?style=flat-square)](https://travis-ci.org/omines/datatables-bundle)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/omines/datatables-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/omines/datatables-bundle/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/omines/datatables-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/omines/datatables-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/omines/datatables-bundle.svg?style=flat-square)](https://packagist.org/packages/omines/datatables-bundle)
[![SensioLabs Insight](https://img.shields.io/sensiolabs/i/05d79ba2-cba4-4201-a17a-2868c51f9c6c.svg)](https://insight.sensiolabs.com/projects/05d79ba2-cba4-4201-a17a-2868c51f9c6c)

This bundle provides convenient integration of the popular [DataTables](https://datatables.net/) jQuery library
for realtime AJAX tables in your Symfony 3.3+ application.

## Installation

To install, use composer:

```bash
$ composer require omines/datatables-bundle
```
Then add the bundle to your kernel's bundle registration:
```php
public function registerBundles()
{
    ...
    new \Omines\DataTablesBundle\DataTablesBundle(),
    ...
}
```

## Usage

TBD.

## Testing

```bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/omines/datatables-bundle/blob/master/CONTRIBUTING.md) for details.

## Legal

This software was developed for internal use at [Omines Full Service Internetbureau](https://www.omines.nl/)
in Eindhoven, the Netherlands. It is shared with the general public under the permissive MIT license, without
any guarantee of fitness for any particular purpose. Refer to the included `LICENSE` file for more details.
