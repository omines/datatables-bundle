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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('datatables');

        $rootNode
            ->children()
                ->booleanNode('languageFromCdn')
                    ->info('Load i18n data from DataTables CDN or locally')
                    ->defaultTrue()
                ->end()
                ->booleanNode('requestState')
                    ->info('Persist request state automatically')
                    ->defaultTrue()
                ->end()
                ->scalarNode('className')
                    ->info('Default class attribute to apply to the root table elements')
                ->end()
                ->enumNode('columnFilter')
                    ->info('If and where to enable the DataTables Filter module')
                    ->values(['thead', 'tfoot', 'both', null])
                    ->defaultNull()
                ->end()
                ->arrayNode('options')
                    ->info('Default options to load into DataTables')
                    ->useAttributeAsKey('name')
                    ->prototype('variable')->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
