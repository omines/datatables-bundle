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
for realtime AJAX tables in your Symfony 3.3+ application.

This library is currently under initial development and only fit for internal use.

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

To render the most basic static table, implement a controller like this:
```php
use Omines\DataTablesBundle\DataTablesTrait;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\TextColumn;

class MyController
{
    use DataTablesTrait;
    
    public function showAction(Request $request)
    {
        $table = $this->createDataTable()
            ->add('firstName', TextColumn::class)
            ->add('lastName', TextColumn::class)
            ->setAdapter(ArrayAdapter::class, [
                ['firstName' => 'Donald', 'lastName' => 'Trump'],
                ['firstName' => 'Barack', 'lastName' => 'Obama'],
            ])
            ->handleRequest($request);
        
        if ($request->isXmlHttpRequest()) {
            return $table->getResponse();
        }
        
        $this->render('list.html.twig', ['datatable' => $table]);
    }
}

```
Now in `list.html.twig` render the required HTML and JS with:
```twig
{{ datatable(datatable)) }}
```
More advanced examples will follow.

## Contributing

Please see [CONTRIBUTING.md](https://github.com/omines/datatables-bundle/blob/master/CONTRIBUTING.md) for details.

## Legal

This software was developed for internal use at [Omines Full Service Internetbureau](https://www.omines.nl/)
in Eindhoven, the Netherlands. It is shared with the general public under the permissive MIT license, without
any guarantee of fitness for any particular purpose. Refer to the included `LICENSE` file for more details.
