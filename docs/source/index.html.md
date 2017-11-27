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

The <code>DataTablesTrait</code> is included to expose 2 methods in your controller for easy instantiation.
The `createDataTable` function is used in this example, the other one will be covered later. On the DataTable
instance we add 2 columns of type `TextColumn`, and we bind it to an adapter providing a static array as the
source of the data.

The `handleRequest` function will take care of handling any postbacks, similar to how Symfony's Form component
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

# Options

Option | Default | Description
------ | ------- | -----------
name   | dt      | The name of the DataTable. Used mainly to separate callbacks in case multiple tables are used on the same page.

