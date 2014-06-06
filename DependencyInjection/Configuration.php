<?php

namespace Intaro\JobQueueBundle\DependencyInjection;

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

        $tree->root('intaro_job_queue')
            ->children()
                ->scalarNode('class')->defaultValue('%intaro_job_queue.job_manager.class%')->end()
                ->scalarNode('job_timeout')->defaultValue(null)->end()
                ->scalarNode('environment')->defaultValue('prod')->end()
                ->booleanNode('durable')->defaultValue(true)->end()
                ->arrayNode('intervals')
                    ->useAttributeAsKey('code')
            ->end();

        return $tree;
    }
}
