<?php

declare(strict_types=1);

namespace Scaffolds\ReviewsReviewTrackers;

use Scaffolds\CommonDefinitions;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use MyComponent\ConfigDefinition as MainConfigDefinition;

class ConfigDefinition implements ConfigurationInterface
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
        $snowflakeTreeBuilder = new TreeBuilder('keboola.wr_db_snowflake');
        $node = CommonDefinitions::getSnowflakeWriterConfig($snowflakeTreeBuilder);
        $scaffoldNode->append($node);

        return $treeBuilder;
    }

    private function appendExtractors(ArrayNodeDefinition $scaffoldNode): void
    {
        // @formatter:off
        $treeBuilder = new TreeBuilder('kds_team.ex_reviewtrackers');
        /** @var ArrayNodeDefinition $node */
        $node = $treeBuilder->getRootNode();
        $node->setPathSeparator(MainConfigDefinition::PATH_SEPARATOR);
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
                        ->booleanNode('clear_state')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end() // parameters
            ->end()
        ;
        // @formatter:on
        $scaffoldNode->append($node);
    }
}
