<?php

namespace Deleto\VersioningBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('versioning');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('api')
                    ->children()
                        ->arrayNode('default')
                            ->children()
                                ->scalarNode('min_version')->end()
                                ->scalarNode('max_version')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
