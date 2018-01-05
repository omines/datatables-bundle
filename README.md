# Symfony DataTables Bundle
[![Latest Stable Version](https://poser.pugx.org/omines/datatables-bundle/version)](https://packagist.org/packages/omines/datatables-bundle)
[![Total Downloads](https://poser.pugx.org/omines/datatables-bundle/downloads)](https://packagist.org/packages/omines/datatables-bundle)
[![Latest Unstable Version](https://poser.pugx.org/omines/datatables-bundle/v/unstable)](//packagist.org/packages/omines/datatables-bundle)
[![License](https://poser.pugx.org/omines/datatables-bundle/license)](https://packagist.org/packages/omines/datatables-bundle)
[![Build Status](https://travis-ci.org/omines/datatables-bundle.svg?branch=master)](https://travis-ci.org/omines/datatables-bundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/omines/datatables-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/omines/datatables-bundle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/omines/datatables-bundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/omines/datatables-bundle/?branch=master)
[![SensioLabs Insight](https://img.shields.io/sensiolabs/i/05d79ba2-cba4-4201-a17a-2868c51f9c6c.svg)](https://insight.sensiolabs.com/projects/05d79ba2-cba4-4201-a17a-2868c51f9c6c)

This bundle provides convenient integration of the popular [DataTables](https://datatables.net/) jQuery library
for realtime Ajax tables in your [Symfony](https://symfony.com/) 3.3+ or 4.0+ application.

Unlike other bundles providing similar functionality we decoupled the implementation of the DataTables logic
completely from the source of the data. Therefore it is possible to implement your own custom adapters for
every possible data source. [Doctrine ORM](https://github.com/doctrine/DoctrineBundle) and [Elastica](https://github.com/ruflin/Elastica) come bundled already, we intend to provide popular
choices like FOSElasticaBundle, Doctrine DBAL and MongoDB out of the box as well. 

## Documentation

[Visit the documentation with extensive code samples](https://omines.github.io/datatables-bundle/).

## Contributing

Please see [CONTRIBUTING.md](https://github.com/omines/datatables-bundle/blob/master/CONTRIBUTING.md) for details.

## Legal

This software was developed for internal use at [Omines Full Service Internetbureau](https://www.omines.nl/)
in Eindhoven, the Netherlands. It is shared with the general public under the permissive MIT license, without
any guarantee of fitness for any particular purpose. Refer to the included `LICENSE` file for more details.
