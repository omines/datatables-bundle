<?php

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
                ->booleanNode('language_from_cdn')->defaultTrue()->end()
                ->enumNode('column_filter')
                    ->values(['thead', 'tfoot', 'both', null])->defaultNull()
                ->end()
                ->arrayNode('options')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
