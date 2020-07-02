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
for realtime AJAX tables in your Symfony 4.1+ application.

Designed to be fully pluggable there are no limits to the data sources you can display through this library, nor
are there any bounds on how they are displayed. In full *'batteries included but replaceable'* philosophy there are
ready made adapters for common use cases like Doctrine ORM, but they are trivial to replace or extend.

# Installation

Recommended way of installing this library is through [Composer](https://getcomposer.org/).

<code>composer require omines/datatables-bundle</code>

Please ensure you are using Symfony 4.1 or later. If you are using Symfony Flex a recipe is included in the contrib
repository, providing automatic installation and configuration.

```php?start_inline=true
public function registerBundles()
{
    // After Symfony's own bundles 
    new \Omines\DataTablesBundle\DataTablesBundle(),
    // Before your application bundles
}
```

After installation, if not using Flex, you should register the bundle to your kernel, commonly `AppKernel.php`,
before your own bundles but after the required external bundles, such as `FrameworkBundle` and `TwigBundle`.

Run the `assets:install` command to deploy the included Javascript files to your application's public folder.

<code>bin/console assets:install</code>

That last step is optional, as you can also load it through Assetic or WebPack, but a good starting point.
 
## Clientside dependencies

```html
<!-- in the <head> section -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/jq-3.2.1/dt-1.10.16/datatables.min.css"/>

<!-- before the closing <body> tag -->
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/jq-3.2.1/dt-1.10.16/datatables.min.js"></script>
```
All serverside dependencies are managed by Composer. Clientside dependencies are left up the implementer to decide
how to include which DataTables dependencies. As long are you are using a fairly up to date version of DataTables you
should be fine, as the bundle does not use exotic features or depend on plugins.

The code snippets here should get you started quickly, including jQuery 3. For more extensive download options visit
[https://datatables.net/download/](https://datatables.net/download/).

# Quickstart

```php?start_inline=true
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;

class MyController extends Controller
{
    public function showAction(Request $request, DataTableFactory $dataTableFactory)
    {
        $table = $dataTableFactory->create()
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
        
        return $this->render('list.html.twig', ['datatable' => $table]);
    }
}
```
This trivial bit of code in your controller prepares a fully functional DataTables instance for use.

The <code>DataTableFactory</code> service is injected to expose convenience methods in your controller
for easy instantiation. The `create` function is used in this example. On the DataTable instance we 
add 2 columns of type `TextColumn`, and we bind it to an adapter providing a static array as the
source of the data.

The `handleRequest` function will take care of handling any callbacks, similar to how Symfony's Form component
works. If it turns out the request originated from a callback we let the table provide the controller response,
otherwise we render a template with the table provided as a parameter.

<aside class="notice">To keep your controller thin you should <a href="#datatable-types">make reusable DataTable types under the DataTable namespace of your app/bundle</a>.</aside>

## Controller setup

Previous versions of this bundle offered a <code>DataTablesTrait</code> which assumed that the
<code>DataTableFactory</code> class was available in the controller's <code>$container</code>. As this
is deprecated in current versions of Symfony you should use dependency injection instead.

## Frontend code

```html
<!-- Insert this where you want the table to appear -->
<div id="presidents">Loading...</div>

<!-- Insert this at the end of your body element, but before the closing tag -->
<script src="{{ asset('bundles/datatables/js/datatables.js') }}"></script>
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
    persist_state:        fragment # One of "none"; "query"; "fragment"; "local"; "session"

    # Default service used to render templates, built-in TwigRenderer uses global Twig environment
    renderer:             Omines\DataTablesBundle\Twig\TwigRenderer

    # Default template to be used for DataTables HTML
    template:             '@DataTables/datatable_html.html.twig'

    # Default parameters to be passed to the template
    template_parameters:

        # Default class attribute to apply to the root table elements
        className:        'table table-bordered'

        # If and where to enable the DataTables Filter module
        columnFilter:     null # One of "thead"; "tfoot"; "both"; null

    # Default translation domain to be used
    translation_domain:   messages
```

Global configuration of the bundle is done in your Symfony config file. The default configuration is shown here,
and should be fine in most cases. Most settings can be overridden per table, but for most applications
you will want to make changes at the global level so they are applied everywhere, providing a uniform
look and feel.

The following settings exist at the configuration level:

Option | Type | Description
------ | ---- | ------- | -----------
language_from_cdn | bool | Load i18n files from DataTables CDN or from Symfony Translations.
options | object | Default options that will be passed to DataTables clientside initialization.
method | string | Either `GET` or `POST` to indicate which HTTP method to use for callbacks.
renderer | string | Service used to render the table HTML, which must implement the <code>DataTableRendererInterface</code>.
template | string | Default template to be used for rendering the basic HTML table in your templates.
template_parameters | object | Default parameters to be passed to the template during rendering.
translation_domains | string | Default Symfony Translation Domain used where translations are used.

All settings can be overridden on individual tables by calling the corresponding setter function,
ie. `setLanguageFromCDN(bool)`.

The `options` are passed (almost) verbatim to the DataTables clientside constructor. Refer to the
[external documentation](https://datatables.net/reference/option/) below for details on individual
options. Only options which are meaningful to be defined serverside can be set at this level, so
setting callbacks and events is not possible. These are however easily set on the Javascript end.

## Table configuration

Configuring a specific table is done mainly via the methods on `DataTable`. The most common call
is `add` to add an extra column to the table as seen in all the examples. More utility methods
exist and can be chained.

`->addOrderBy($column, string $direction = DataTable::SORT_ASCENDING)`  
Will set the default sort of the table to the specified column and direction. Repeatable to sort
by multiple columns.

# Adapters

Adapters are the core elements bridging DataTables functionality to their underlying data source.
Popular implementations for common data sources are provided, and more are welcomed.

An adapter is called by the bundle when a request for data has been formulated, including search
and sorting criteria, and returns a result set with metadata on record counts.

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

The `ORMAdapter` has a single mandatory property `entity`, which should be set to the full FQCN of the main
entity the table is showing.

Underneath a lot of "magic" is happening in this most simple of examples. The first 2 columns automatically
have their `field` option defaulted to the "root entity" of the adapter, with the field identical to their
name. The adapter itself did not get a query, and as such injected the `AutomaticQueryBuilder` supplied by
this bundle, which scans the metadata and automatically joins and selects the right data based on the fields.
Secondly, since no criteria processors were supplied a default `SearchCriteriaProvider` was injected to
apply global search to all mapped fields.

Of course, all of this is just convenient default. For more complex scenarios you can supply your own query
builders and criteria providers, and even chain them together to easily implement multiple slightly different
tables in your site.

### Customizing queries

```php?start_inline=1
$table->createAdapter(ORMAdapter::class, [
    'entity' => Employee::class,
    'query' => function (QueryBuilder $builder) {
        $builder
            ->select('e')
            ->addSelect('c')
            ->from(Employee::class, 'e')
            ->leftJoin('e.company', 'c')
        ;
    },
]);       
```
If you do not specify the `query` option the stock `AutomaticQueryBuilder` is used, which automatically
joins the main entity to its relationships recursively to select *only* the fields defined by the columns.
This works fine in many cases, in others you may need to customize the query.

The `query` property can be set to a single instance or an array of processors, which can either be
callables taking a single `QueryBuilder` as a parameter, or a (anonymous) class implementing the
`QueryBuilderProcessorInterface`. In case of an array the processors are called in the defined order.

In general it is recommended to implement your own query processor completely when you need custom
behavior. Chaining on the default `AutomaticQueryBuilder` is possible, but may cause unexpected
interaction based on internal changes in this bundle and/or Doctrine ORM. 

### Customizing criteria

```php?start_inline=1
$table->createAdapter(ORMAdapter::class, [
    'entity' => Employee::class,
    'query' => [
        function (QueryBuilder $builder) {
            $builder->andWhere($builder->expr()->like('c.name', ':test'))->setParameter('test', '%ny 2%');
        },
        new SearchCriteriaProvider(),
    ],
]);
```             
Analogous to queries you can separately define the criteria processors applied to table queries. The
`criteria` property also takes a single instance or an array, with the separate processors either
implementing `QueryBuilderProcessorInterface`, or being a callback returning a `Criteria` object as
in this example.

Note that implementing your own criteria overrides the default, meaning searching and sorting will no
longer work automatically. Add the `SearchCriteriaProvider` manually to combine the default behavior
with your own implementation.

### Events

```php?start_inline=1
$table->createAdapter(ORMAdapter::class, [
    'entity' => Employee::class,
    'query' => function (QueryBuilder $builder) {
        $builder
            ->select('e')
            ->addSelect('c')
            ->from(Employee::class, 'e')
            ->leftJoin('e.company', 'c')
        ;
    },
]);

$table->addEventListener(ORMAdapterEvents::PRE_QUERY, function(ORMAdapterQueryEvent $event) {
    $event->getQuery()->useResultCache(true)->useQueryCache(true);
});
```
The `PRE_QUERY` event is dispatched after the QueryBuilder built the Query
and before the iteration starts. It can be useful to configure the cache.

## Elastica

```php?start_inline=1
use Omines\DataTablesBundle\Adapter\Elasticsearch\ElasticaAdapter;

$table = $this->createDataTable()
    ->setName('log')
    ->add('timestamp', DateTimeColumn::class, ['field' => '@timestamp', 'format' => 'Y-m-d H:i:s', 'orderable' => true])
    ->add('level', MapColumn::class, [
        'default' => '<span class="label label-default">Unknown</span>',
        'map' => ['Emergency', 'Alert', 'Critical', 'Error', 'Warning', 'Notice', 'Info', 'Debug'],
    ])
    ->add('message', TextColumn::class, ['globalSearchable' => true])
    ->createAdapter(ElasticaAdapter::class, [
        'client' => ['host' => 'elasticsearch'],
        'index' => 'logstash-*',
    ]);
```
If you have installed `ruflin/elastica` you can use the provided `ElasticaAdapter` to use ElasticSearch
indexes as the data source.

## MongoDB

```php?start_inline=1
use Omines\DataTablesBundle\Adapter\MongoDB\MongoDBAdapter;

$table = $this->createDataTable()
    ->add('name', TextColumn::class)
    ->add('company', TextColumn::class)
    ->createAdapter(MongoDBAdapter::class, [
        'collection' => 'myCollection',
    ]);
```
If you have installed `mongodb/mongodb` you can use the provided `MongoDBAdapter` to use MongoDB
collections as the data source.

## Arrays

TBD.

## Implementing custom adapters

TBD.

# Columns

Column classes derive from `AbstractColumn`, and implement the transformations required to convert
raw data into output ready for rendering in a DataTable.

A number of standard columns are provided for common use cases, but you can easily add your own column
types for application specific purposes.

### Common options

```php?start_inline=1
# Some example columns
$table
    ->add('firstName', TextColumn::class, ['label' => 'customer.name', 'className' => 'bold'])
    ->add('lastName', TextColumn::class, ['render' => '<strong>%s</strong>', 'raw' => true])
    ->add('email', TextColumn::class, ['render' => function($value, $context) {
        return sprintf('<a href="%s">%s</a>', $value, $value);
    }])
;
```

All column types have the following options:

Option | Type | Description
------ | ---- | -----------
label | string | Basic translation label shown in the header of the table. Defaults to the name of the column.
data | string/callable/`null` | The default value if a `null` value is encountered, or a callable function to transform data. 
field | string/`null` | A field mapping to be used by adapters to fill data.
propertyPath | string/`null` | A property path to be applied to the raw adapter row data.
visible | bool | Whether the column will be visible. Default true.
orderable | bool/`null` | Whether the column can be sorted upon. Defaults to the presence of the `orderField`. 
orderField | string/`null` | The field to order by when the column is sorted. Defaults to the value of `field`.
searchable | bool/`null` | Whether the column can be searched upon. Defaults to the presence of `field`.
globalSearchable | bool/`null` | Whether the column participates in global searches. Defaults to the presence of `field`.
className | string/`null` | A CSS class to be applied to all cells in this column.
render | string/callable/`null` | Either a [`sprintf` compatible format string](http://php.net/manual/en/function.sprintf.php), or a callable function providing rendering conversion, or default `null`.

## TextColumn

```php?start_inline=1
$table->add('customerName', TextColumn::class, ['field' => 'customer.name']);
```

Text columns are the most frequently used column type, as they can be used to display any kind of data
that is eventually rendered as plain text.

The `TextColumn` type exposes a single option on top of its ancestor `AbstractColumn`:

Option | Type | Description
------ | ---- | -----------
raw | bool | Do not escape cell content to be safe for use in HTML. Default `false`.

## BoolColumn

```php?start_inline=1
$table->add('wantsNewsletter', BoolColumn::class, [
    'trueValue' => 'yes',
    'falseValue' => 'no',
    'nullValue' => 'unknown',
]);
```

Bool columns render a boolean value, which is allowed to be indeterminate (`null`). Three properties define
how values are rendered:

Option | Type | Description
------ | ---- | -----------
trueValue | string | Raw string to use for true-ish values. Default `true`.
falseValue | string | Raw string to use for false-ish values. Default `false`.
nullValue | string | Raw string to use for null values. Default `null`.

## DateTimeColumn

```php?start_inline=1
$table->add('registrationDate', DateTimeColumn::class, ['format' => 'd-m-Y']);
```

DateTime columns render a `\DateTimeInterface` implementing class, such as `\DateTime`, to a string
result. If data of other types is encountered automatic conversion is attempted following [common PHP formats](http://php.net/manual/en/datetime.formats.php).

Option | Type | Description
------ | ---- | -----------
createFromFormat | string | Custom format for creating DateTime objects from values. A format string accepted by [`DateTime::createFromFormat()`](https://www.php.net/manual/en/datetime.createfromformat.php) function.
format | string | A date format string as accepted by the [`date()`](http://php.net/manual/en/function.date.php) function. Default `'c'`.
nullValue | string | Raw string to display for null values. Defaults to the empty string.

## MapColumn

```php?start_inline=1
$table->add('gender', MapColumn::class, [
    'default' => 'not provided',
    'map' => [
        'f' => 'Female',
        'm' => 'Male',
    ],
]);
```

Map columns used to transform a discrete collection of values into proper display counterparts. This
can be useful to convert enumerated fields such as log severity levels, or genders as in the example.
Fields which are not present in the column's map return the default value, if this is `null` the 
source value itself is shown unmodified.

The `MapColumn` type extends `TextColumn`, as such inheriting the `raw` property, while adding its own:

Option | Type | Description
------ | ---- | -----------
default | string/`null` | Value to be shown for source data not found in the map. Default `null`.
map | array | Associative array containing available mappings. Mandatory without default.

## TwigColumn

```php?start_inline=1
$table->add('buttons', TwigColumn::class, [
    'className' => 'buttons',
    'template' => 'tables/buttonbar.html.twig',
])
```

This column type allows you to specify a Twig template used to render the column's cells. The
template is rendered using the main application context by injecting the main Twig service.
Additionally the `value` and `row` parameters are being filled by the cell value and the row
level context respectively.

Option | Type | Description
------ | ---- | -----------
template | string | Template path resolvable by the Symfony templating component. Required without default.

<aside class="warning">Keep in mind that for most simple use cases a decorated TextColumn will perform better than a full Twig template per row.</aside>

## TwigStringColumn

```php?start_inline=1
$table->add('link', TwigStringColumn::class, [
    'template' => '<a href="{{ url(\'employee.edit\', {id: row.id}) }}">{{ row.firstName }} {{ row.lastName }}</a>',
])
```

This column type allows you to inline a Twig template as a string used to render the column's cells. The
template is rendered using the main application context by injecting the main Twig service.
Additionally, the `value` and `row` parameters are being filled by the cell value and the row
level context respectively.

This column type requires `StringLoaderExtension` to be [enabled in your Twig environment](https://symfony.com/doc/4.4/reference/dic_tags.html#twig-extension).

```yaml
services:
    Twig\Extension\StringLoaderExtension:
        tags: [twig.extension]
```

Option | Type | Description
------ | ---- | -----------
template | string | Template content resolvable by the Symfony templating component. Required without default.

<aside class="warning">Keep in mind that for most simple use cases a decorated TextColumn will perform better than a full Twig template per row.</aside>

## Implementing custom columns

TBD.

# DataTable Types

```php?start_inline=1
$table = $this->createDataTableFromType(PresidentsTableType::class)
    ->handleRequest($request);
```

Having the table configuration in your controller is convenient, but not practical for reusable or
extensible tables, or highly customized tables. In the example above we could also create a class
`DataTable\Type\PresidentsTableType` in our app bundle, and make it implement 
`Omines\DataTablesBundle\DataTableTypeInterface`. We can then use the code illustrated here to
instantiate the reusable class in the controller.

This ensures your controllers stay lean and short, and only delegate tasks. The first parameter
takes either a Fully Qualified Class Name (FQCN) to instantiate the class dynamically, or a
registered service with a `datatables.type` tag. Use a service if you need to inject dependencies
dynamically. When using Symfony's autoconfiguration the tag will be applied automatically.

Of course you can modify the base type to fit the controller's specific needs before calling 
`handleRequest`. Secondly, the `createDataTableFromType` function accepts an array as a second
argument which is passed to the type class for parametrized instantiation.

# Javascript

```javascript
$('#table1').initDataTables({{ datatable_settings(datatable1) }}, {
    searching: true,
    dom:'<"html5buttons"B>lTfgitp',
    buttons: [
        'copy',
        { extend: 'pdf', title: 'domains'},
        { extend: 'print' }
    ]
}).then(function(dt) {
    // dt contains the initialized instance of DataTables
    dt.on('draw', function() {
        alert('Redrawing table');
    })
});
```
During the quickstart we introduced the `initDataTables` Javascript function, taking the serverside
settings as its argument. The function takes an optional second argument, which is merged into the
serverside settings to override any template-specific changes, but as this is executed in the browser
it also means this is where you can add Javascript events according to DataTables documentation.

The function returns a [`Promise`](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Promise)
which is fulfilled with the `DataTables` instance once initialization is completed. This allows you
all the flexibility you could need to [invoke API functions](https://datatables.net/reference/api/).

# Legal

This software was developed for internal use at [Omines Full Service Internetbureau](https://www.omines.nl/)
in Eindhoven, the Netherlands. It is shared with the general public under the permissive MIT license, without
any guarantee of fitness for any particular purpose. Refer to the included `LICENSE` file for more details.
