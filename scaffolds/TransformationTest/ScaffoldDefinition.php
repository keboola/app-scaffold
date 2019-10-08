<?php

declare(strict_types=1);

namespace Keboola\Scaffolds\TransformationTest;

use Keboola\ScaffoldApp\ScaffoldInputDefinitionInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ScaffoldDefinition implements ScaffoldInputDefinitionInterface
{
    public function addInputsDefinition(
        ArrayNodeDefinition $node
    ): ArrayNodeDefinition {
        $node->children()
            ->arrayNode('connectionWriter')
                ->children()
                    ->scalarNode('url')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('#token')->isRequired()->cannotBeEmpty()->end()
                ->end()
            ->end()
            ->arrayNode('snowflakeExtractor')
                ->children()
                    ->arrayNode('db')
                        ->children()
                            ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('user')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('schema')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('database')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('#password')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('warehouse')->isRequired()->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->end();
        return $node;
    }
}
