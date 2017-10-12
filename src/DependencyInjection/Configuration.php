<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DatatablesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('datatables');

        $rootNode
            ->children()
            ->booleanNode('languageFromCdn')->defaultTrue()->end()
            ->booleanNode('requestState')->defaultTrue()->end()
            ->scalarNode('class')->end()
            ->enumNode('columnFilter')
            ->values(['thead', 'tfoot', 'both', null])->defaultNull()
            ->end()
            ->arrayNode('options')
            ->useAttributeAsKey('name')
            ->prototype('variable')->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
