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
        $treeBuilder = new TreeBuilder('ReviewsReviewTrackers');
        /** @var ArrayNodeDefinition $scaffoldNode */
        $scaffoldNode = $treeBuilder->getRootNode();
        $this->appendExtractors($scaffoldNode);
        // writer parameters
        $snowflakeTreeBuilder = new TreeBuilder('writer01');
        $node = CommonDefinitions::getSnowflakeWriterConfig($snowflakeTreeBuilder);
        $scaffoldNode->append($node);
        return $treeBuilder;
    }

    private function appendExtractors(ArrayNodeDefinition $scaffoldNode): void
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
        $scaffoldNode->append($node);
    }
}
