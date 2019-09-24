<?php

declare(strict_types=1);

namespace Keboola\Scaffolds\ReviewsReviewTrackers;

use Keboola\Scaffolds\CommonDefinitions;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ScaffoldDefinition implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('parameters');
        /** @var ArrayNodeDefinition $parametersNode */
        $parametersNode = $treeBuilder->getRootNode();
        $this->appendExtractors($parametersNode);
        // writer parameters
        $snowflakeTreeBuilder = new TreeBuilder('writer01');
        $node = CommonDefinitions::getSnowflakeWriterConfig($snowflakeTreeBuilder);
        $parametersNode->append($node);
        return $treeBuilder;
    }

    private function appendExtractors(ArrayNodeDefinition $parametersNode): void
    {
        // @formatter:off
        $treeBuilder = new TreeBuilder('ex01');
        /** @var ArrayNodeDefinition $node */
        $node = $treeBuilder->getRootNode();
        $node
            ->children()
                ->arrayNode('parameters')
                    ->children()
                        ->scalarNode('username')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('#password')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('clear_state')
                            ->defaultValue('true')
                        ->end()
                    ->end()
                ->end() // parameters
            ->end()
        ;
        // @formatter:on
        $parametersNode->append($node);
    }
}
