---
title: Symfony DataTables Bundle

toc_footers:
  - <a href='https://packagist.org/packages/omines/datatables-bundle'>Install from Packagist</a>
  - <a href='https://github.com/omines/datatables-bundle'>Fork me on GitHub</a>
  - <a href='https://github.com/omines/datatables-bundle/issues'>Report an issue</a>
  - <a href='https://www.omines.nl/' title='Internetbureau Eindhoven'>Omines Full Service Internetbureau</a>

search: true
---

# Introduction

This bundle provides convenient integration of the popular [DataTables](https://datatables.net/) jQuery library
for realtime AJAX tables in your Symfony 3.3+ or 4.0+ application.

Designed to be fully pluggable there are no limits to the data sources you can display through this library, nor
are there any bounds on how they are displayed. In full *'batteries included but replaceable'* philosophy there are
ready made adapters for common use cases like Doctrine ORM, but they are trivial to replace or extend.

# Installation

Recommended way of installing this library is through [Composer](https://getcomposer.org/).

<code>composer require omines/datatables-bundle</code>

Please ensure you are using Symfony 3.3 or later. Symfony Flex bindings are on their way.

```php?start_inline=true
public function registerBundles()
{
    // After Symfony's own bundles 
    new \Omines\DataTablesBundle\DataTablesBundle(),
    // Before your application bundles
}
```

After installation you should register the bundle to your kernel, commonly `AppKernel.php`, before your
own bundles but after the required external bundles, such as `FrameworkBundle` and `TwigBundle`.

Run the `assets:install` command to deploy the included Javascript files to your application's public folder.

<code>bin/console assets:install</code>

<aside class="notice">That last step is actually optional, as you can also load it through Assetic or WebPack, but a good starting point.</aside>

# Quickstart

```php?start_inline=true
use Omines\DataTablesBundle\DataTablesTrait;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\TextColumn;

class MyController extends Controller
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
        
        if ($table->isCallback()) {
            return $table->getResponse();
        }
        
        $this->render('list.html.twig', ['datatable' => $table]);
    }
}
```
This trivial bit of code in your controller prepares a fully functional DataTables instance for use.

The optional <code>DataTablesTrait</code> is included to expose convenience methods in your controller for
easy instantiation. The `createDataTable` function is used in this example. On the DataTable instance we 
add 2 columns of type `TextColumn`, and we bind it to an adapter providing a static array as the
source of the data.

The `handleRequest` function will take care of handling any callbacks, similar to how Symfony's Form component
works. If it turns out the request originated from a callback we let the table provide the controller response,
otherwise we render a template with the table provided as a parameter.

## Frontend code

```html
<!-- Insert this where you want the table to appear -->
<div id="presidents">Loading...</div>

<!-- Insert this at the end of your body element, but before the closing tag -->
<script src="bundles/datatables/js/datatables.js"></script>
<script>
$(function() {
    $('#presidents').initDataTables({{ datatable_settings(datatable) }});
});
</script>
```

In your Twig template, `list.html.twig` in the example, we need to ensure the HTML has a container element
ready to contain the table. During load its contents will be erased, so you can put a loading indicator in
there like we did here.

Then you include the Javascript deployed to your public folder, and run a single command on a jQuery
selection of the container element. The `datatable_settings` Twig function will render a compact JSON
string with the configured settings required for initialization.

And that's it, the library will take it from here and your table will be shown on your webpage!

# Configuration

```yaml
datatables:

    # Load i18n data from DataTables CDN or locally
    language_from_cdn:    true

    # Default HTTP method to be used for callbacks
    method:               POST # One of "GET"; "POST"

    # Default options to load into DataTables
    options:
        option:           value           

    # Where to persist the current table state automatically
    persist_state:        fragment # One of "query"; "fragment"; "local"; "session"

    # Default service used to render templates, built-in TwigRenderer uses global Twig environment
    renderer:             Omines\DataTablesBundle\Twig\TwigRenderer

    # Default template to be used for DataTables HTML
    template:             '@DataTables/datatable_html.html.twig'

    # Default parameters to be passed to the template
    template_parameters:

        # Default class attribute to apply to the root table elements
        className:            'table table-bordered'

        # If and where to enable the DataTables Filter module
        columnFilter:         null # One of "thead"; "tfoot"; "both"; null

    # Default translation domain to be used
    translation_domain:   messages
```

Global configuration of the bundle is done in your config file. The default configuration is shown here,
and should be fine in most cases. Most settings can be overridden per table, but for most applications
you will want to make changes at the global level so they are applied everywhere, providing a uniform
look and feel.

The following settings only exist at the configuration level:

Option | Type | Description
------ | ---- | ------- | -----------
template | string | Default template to be used for rendering the basic HTML table in your templates.
renderer | string | Service used to render the table HTML, which must implement the <code>DataTableRendererInterface</code>.  

The other settings correspond to table level settings, and the `options` are passed (almost) verbatim
to the DataTables clientside constructor. Refer to the sections below for details on individual settings
and options.

## Settings

These settings can all be defined both at the global config level and overridden per table.

Setting | Type | Default | Description
------- | ---- | ------- | -----------
name   | string | dt | The name of the DataTable. Used mainly to separate callbacks in case multiple tables are used on the same page.
method | string | POST | Use `GET` or `POST` to define the HTTP method used by callbacks.
class_name | string | | Class to apply to the `<table>` element in generated tables. Separate multiple classes with a space.
column_filter | string | *null* | When using column level filters set this to `thead`, `tfoot` or `both` to specify where to render them.
language_from_cdn | bool | true | Either loads DataTables' own translations from CDN (default) or have them provided by your own Symfony translation files.
translation_domain | string | messages | Default translation domain used in the table structure.

## Options

# Core concepts

## DataTable types

Having the table configuration in your controller is convenient, but not practical for reusable or
extensible tables, or highly customized tables.

In the example above we could also create a class `DataTable\Type\PresidentsTableType` in our app bundle,
and make it implement `Omines\DataTablesBundle\DataTableTypeInterface`. We can then use:

```php?start_inline=1
    $table = $this->createDataTableFromType(PresidentsTableType::class)
        ->handleRequest($request);
```
This ensures your controllers stay lean and short, and only delegate tasks. Of course you can modify
the base type to fit the controller's specific needs before calling `handleRequest`.

If you need dependencies injected just register `PresidentsTableType` as a service in the container, and
tag it with `datatables.type`. Or just use `autoconfigure:true` as is recommended Symfony practice.

# Adapters

Ready-made adapters are supplied for easy integration with various data sources.

## Doctrine ORM

```php?start_inline=1
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;

$table = $this->createDataTable()
    ->add('firstName', TextColumn::class)
    ->add('lastName', TextColumn::class)
    ->add('company', TextColumn::class, ['field' => 'company.name'])
    ->createAdapter(ORMAdapter::class, [
        'entity' => Employee::class,
    ]);
```
If you have installed `doctrine/orm` and `doctrine/doctrine-bundle` you can use the provided `ORMAdapter`.
Assume a simple `Employee` table with some basic fields and a ManyToOne relationship to `Company` for
these examples.
 
Underneath a lot of "magic" is happening in this most simple of examples. The first 2 columns automatically
have their `field` option defaulted to the "root entity" of the adapter, with the field identical to their
name. The adapter itself did not get a query, and as such injected the `AutomaticQueryBuilder` supplied by
this bundle, which scans the metadata and automatically joins and selects the right data based on the fields.
Secondly, since no criteria processors were supplied a default `SearchCriteriaProvider` was injected to
apply global search to all mapped fields.

Of course, all of this is just convenient default. For more complex scenarios you can supply your own query
builders and criteria providers, and even chain them together to easily implement multiple slightly different
tables in your site.

## Arrays

## Implementing your own
