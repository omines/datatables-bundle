<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->defaults()
        ->private()
        ->autowire()
        ->autoconfigure();

    $services->set(\Omines\DataTablesBundle\Adapter\ArrayAdapter::class);

    $services->set(\Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter::class)
        ->args([service('doctrine')->nullOnInvalid()]);

    $services->set(\Omines\DataTablesBundle\Adapter\Doctrine\FetchJoinORMAdapter::class)
        ->args([service('doctrine')->nullOnInvalid()]);

    $services->set(\Omines\DataTablesBundle\Column\TwigColumn::class)
        ->args([service('twig')->nullOnInvalid()]);

    $services->set(\Omines\DataTablesBundle\Column\TwigStringColumn::class)
        ->args([service('twig')->nullOnInvalid()]);

    $services->set(\Omines\DataTablesBundle\Exporter\DataTableExporterCollection::class)
        ->args([tagged_iterator('datatables.exporter')]);

    $services->set(\Omines\DataTablesBundle\Exporter\DataTableExporterManager::class)
        ->args([
            service(\Omines\DataTablesBundle\Exporter\DataTableExporterCollection::class),
            service('translator'),
        ]);

    $services->set(\Omines\DataTablesBundle\Exporter\Excel\ExcelExporter::class)
        ->tag('datatables.exporter');

    $services->set(\Omines\DataTablesBundle\Exporter\Excel\ExcelOpenSpoutExporter::class)
        ->tag('datatables.exporter');

    $services->set(\Omines\DataTablesBundle\Exporter\Csv\CsvExporter::class)
        ->tag('datatables.exporter');

    $services->set(\Omines\DataTablesBundle\DataTableFactory::class)
        ->public()
        ->args([
            '%datatables.config%',
            service('datatables.renderer'),
            service(\Omines\DataTablesBundle\DependencyInjection\Instantiator::class),
            service('event_dispatcher'),
        ]);

    $services->set(\Omines\DataTablesBundle\DependencyInjection\Instantiator::class);

    $services->set(\Omines\DataTablesBundle\Twig\DataTablesExtension::class);

    $services->set(\Omines\DataTablesBundle\Twig\TwigRenderer::class)
        ->args([service('twig')->nullOnInvalid()]);
};
