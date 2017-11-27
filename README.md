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
for realtime AJAX tables in your Symfony 3.3+ or 4.0+ application. Older versions of Symfony [will not be supported](https://github.com/omines/datatables-bundle/issues/1).

Unlike other bundles providing similar functionality we decoupled the implementation of the DataTables logic
completely from the source of the data. Therefore it is possible to implement your own custom adapters for
every possible data source. Doctrine ORM comes bundled already, we intend to provide popular choices like
Elastica, Doctrine DBAL and MongoDB out of the box as well. 

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

To render the most basic table with predefined data, implement a controller like this:
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
            ->createAdapter(ArrayAdapter::class, [
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
Now in your Twig template render the required HTML and JS with:
```twig
{{ datatable(datatable)) }}
```

#### Making a separate datatable type

Having the table configuration in your controller is convenient, but not practical for reusable or
extensible tables, or highly customized tables.

In the example above we could also create a class `DataTable\Type\PresidentsTableType` in our app bundle,
and make it implement `Omines\DataTablesBundle\DataTableTypeInterface`. We can then use:

```php
    $table = $this->createDataTableFromType(PresidentsTableType::class)
        ->handleRequest($request);
```
This ensures your controllers stay lean and short, and only delegate tasks. Of course you can modify
the base type to fit the controller's specific needs before calling `handleRequest`.

If you need dependencies injected just register `PresidentsTableType` as a service in the container, and
tag it with `datatables.type`. Or just use `autoconfigure:true` as is recommended Symfony practice.

### Doctrine integration

If you have installed `doctrine/doctrine-bundle` several convenient wrappers are available to easily make
highly flexible tables.

#### Doctrine ORM

If you have installed `doctrine/orm` you can use the provided `ORMAdapter`. Assume a simple `Employee` table
with some basic fields and a ManyToOne relationship to `Company`:
```php
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
...

        $table = $this->createDataTable()
            ->add('firstName', TextColumn::class)
            ->add('lastName', TextColumn::class)
            ->add('company', TextColumn::class, ['field' => 'company.name'])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Employee::class,
            ])
```
That's all actually! The table will even be searchable.
 
Underneath a lot of "magic" is happening in this most simple of examples. The first 2 columns automatically
have their `field` option defaulted to the "root entity" of the adapter, with the field identical to their
name. The adapter itself did not get a query, and as such injected the `AutomaticQueryBuilder` supplied by
this bundle, which scans the metadata and automatically joins and selects the right data based on the fields.
Secondly, since no criteria processors were supplied a default `SearchCriteriaProvider` was injected to
apply global search to all mapped fields.

Of course, all of this is just convenient default. For more complex scenarios you can supply your own query
builders and criteria providers, and even chain them together to easily implement multiple slightly different
tables in your site.

### Advanced
More advanced examples will follow.

## Contributing

Please see [CONTRIBUTING.md](https://github.com/omines/datatables-bundle/blob/master/CONTRIBUTING.md) for details.

## Legal

This software was developed for internal use at [Omines Full Service Internetbureau](https://www.omines.nl/)
in Eindhoven, the Netherlands. It is shared with the general public under the permissive MIT license, without
any guarantee of fitness for any particular purpose. Refer to the included `LICENSE` file for more details.
