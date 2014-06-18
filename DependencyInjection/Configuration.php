<?php

namespace Intaro\NativeQueryBuilderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $tree = new TreeBuilder();
        $tree->root('intaro_native_query_builder')
            ->children()
                ->scalarNode('cache_time')->defaultValue(0)->end()
            ->end();

        return $tree;
    }
}
