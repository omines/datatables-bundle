<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle;

use Omines\DataTablesBundle\Adapter\AdapterInterface;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Adapter\Doctrine\FetchJoinORMAdapter;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\Column\TwigStringColumn;
use Omines\DataTablesBundle\DependencyInjection\Compiler\LocatorRegistrationPass;
use Omines\DataTablesBundle\DependencyInjection\Instantiator;
use Omines\DataTablesBundle\Exporter\Csv\CsvExporter;
use Omines\DataTablesBundle\Exporter\DataTableExporterCollection;
use Omines\DataTablesBundle\Exporter\DataTableExporterInterface;
use Omines\DataTablesBundle\Exporter\DataTableExporterManager;
use Omines\DataTablesBundle\Exporter\Excel\ExcelExporter;
use Omines\DataTablesBundle\Exporter\Excel\ExcelOpenSpoutExporter;
use Omines\DataTablesBundle\Filter\AbstractFilter;
use Omines\DataTablesBundle\Twig\DataTablesExtension;
use Omines\DataTablesBundle\Twig\TwigRenderer;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @phpstan-type DataTablesConfiguration array{languageFromCDN: bool, renderer: string}
 */
class DataTablesBundle extends AbstractBundle
{
    protected string $extensionAlias = 'datatables';

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new LocatorRegistrationPass());
    }

    /**
     * @param DataTablesConfiguration $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();
        $services->defaults()->autowire()->autoconfigure()->private();

        // Main services
        $services->set(Instantiator::class);
        $services->set(DataTableFactory::class)
            ->public()
            ->arg(0, '%datatables.config%')
            ->arg(1, new Reference('datatables.renderer'))
            ->arg(2, new Reference(Instantiator::class))
            ->arg(3, new Reference('event_dispatcher'))
        ;

        // Adapters
        $services->set(ArrayAdapter::class);
        $services->set(ORMAdapter::class)
            ->arg(0, new Reference('doctrine', ContainerInterface::NULL_ON_INVALID_REFERENCE));
        $services->set(FetchJoinORMAdapter::class)
            ->arg(0, new Reference('doctrine', ContainerInterface::NULL_ON_INVALID_REFERENCE));

        // Columns
        $services->set(TwigColumn::class)
            ->arg(0, new Reference('twig', ContainerInterface::NULL_ON_INVALID_REFERENCE));
        $services->set(TwigStringColumn::class)
            ->arg(0, new Reference('twig', ContainerInterface::NULL_ON_INVALID_REFERENCE));

        $services->set(ExcelExporter::class)
            ->tag('datatables.exporter');
        $services->set(ExcelOpenSpoutExporter::class)
            ->tag('datatables.exporter');
        $services->set(CsvExporter::class)
            ->tag('datatables.exporter');

        // Support services
        $services->set(DataTablesExtension::class);
        $services->set(TwigRenderer::class)
            ->arg(0, new Reference('twig', ContainerInterface::NULL_ON_INVALID_REFERENCE));

        // Exporters
        $services->instanceof(DataTableExporterInterface::class)->tag('datatables.exporter');
        $services->set(DataTableExporterCollection::class)->args([new TaggedIteratorArgument('datatables.exporter')]);
        $services->set(DataTableExporterManager::class)
            ->arg(0, new Reference(DataTableExporterCollection::class))
            ->arg(1, new Reference('translator'));

        // Aliases and parameters
        $builder->setAlias('datatables.renderer', $config['renderer']);
        unset($config['renderer']);
        $builder->setParameter('datatables.config', $config);

        // Autoconfiguration
        $builder->registerForAutoconfiguration(AbstractColumn::class)
            ->addTag('datatables.column')
            ->setShared(false);
        $builder->registerForAutoconfiguration(AbstractFilter::class)
            ->addTag('datatables.filter')
            ->setShared(false);
        $builder->registerForAutoconfiguration(AdapterInterface::class)
            ->addTag('datatables.adapter')
            ->setShared(false);
        $builder->registerForAutoconfiguration(DataTableTypeInterface::class)
            ->addTag('datatables.type');
        $builder->registerForAutoconfiguration(DataTableExporterInterface::class)
            ->addTag('datatables.exporter');
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $rootNode = $definition->rootNode();
        assert($rootNode instanceof ArrayNodeDefinition);

        $rootNode
            ->children()
                ->booleanNode('language_from_cdn')
                    ->info('Load i18n data from DataTables CDN or locally')
                    ->defaultTrue()
                ->end()
                ->enumNode('persist_state')
                    ->info('Where to persist the current table state automatically')
                    ->values(['none', 'query', 'fragment', 'local', 'session'])
                    ->defaultValue('fragment')
                ->end()
                ->enumNode('method')
                    ->info('Default HTTP method to be used for callbacks')
                    ->values([Request::METHOD_GET, Request::METHOD_POST])
                    ->defaultValue(Request::METHOD_POST)
                ->end()
                ->arrayNode('options')
                    ->info('Default options to load into DataTables')
                    ->useAttributeAsKey('option')
                    ->prototype('variable')->end()
                ->end()
                ->scalarNode('renderer')
                    ->info('Default service used to render templates, built-in TwigRenderer uses global Twig environment')
                    ->defaultValue(TwigRenderer::class)
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('template')
                    ->info('Default template to be used for DataTables HTML')
                    ->defaultValue(DataTable::DEFAULT_TEMPLATE)
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('template_parameters')
                    ->info('Default parameters to be passed to the template')
                    ->addDefaultsIfNotSet()
                    ->ignoreExtraKeys()
                    ->children()
                        ->scalarNode('className')
                            ->info('Default class attribute to apply to the root table elements')
                            ->defaultValue('table table-bordered')
                            ->cannotBeEmpty()
                        ->end()
                        ->enumNode('columnFilter')
                            ->info('If and where to enable the DataTables Filter module')
                            ->values(['thead', 'tfoot', 'both', null])
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('translation_domain')
                    ->info('Default translation domain to be used')
                    ->defaultValue('messages')
                ->end()
            ->end()
        ;
    }
}
