<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\DependencyInjection;

use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\Twig\TwigRenderer;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;

class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('datatables');
        $rootNode = method_exists(TreeBuilder::class, 'getRootNode') ? $treeBuilder->getRootNode() : $treeBuilder->root('datatables');

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

        return $treeBuilder;
    }
}
