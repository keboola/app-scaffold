<?php

declare(strict_types=1);

namespace Keboola\Scaffolds\ReviewsReviewTrackers;

use Keboola\Scaffolds\CommonDefinitions;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ScaffoldDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();

        // extractor parameters
        $this->appendKdsTeamExReviewtrackers($parametersNode);

        // writer parameters
        $snowflakeTreeBuilder = new TreeBuilder('configuration_a874');
        $node = CommonDefinitions::getSnowflakeWriterConfig($snowflakeTreeBuilder);
        $parametersNode->append($node);

        return $parametersNode;
    }

    private function appendKdsTeamExReviewtrackers(ArrayNodeDefinition $parametersNode): void
    {
        // @formatter:off
        $treeBuilder = new TreeBuilder('configuration_4b21');
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
