<?php

declare(strict_types=1);

namespace Keboola\Scaffolds\CRMMMR_Salesforce;

use Keboola\ScaffoldApp\ScaffoldInputDefinitionInterface;
use Keboola\Scaffolds\CommonDefinitions;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ScaffoldDefinition implements ScaffoldInputDefinitionInterface
{
    public function addInputsDefinition(
        ArrayNodeDefinition $node
    ): ArrayNodeDefinition {
        // Add salesforce extractor
        $this->appendSalesForceExtractor($node);
        // Add snowflake writer parameters
        $node->append(
            CommonDefinitions::getSnowflakeWriterConfig(
                new TreeBuilder('keboolaWrDbSnowflakeLooker')
            )
        );
        return $node;
    }

    private function appendSalesForceExtractor(
        ArrayNodeDefinition $scaffoldNode
    ): void {
        // @formatter:off
        $treeBuilder = new TreeBuilder('htnsExSalesforceMRR');
        /** @var ArrayNodeDefinition $extractorNode */
        $extractorNode = $treeBuilder->getRootNode();
        $extractorNode->isRequired();
        $extractorNode
            ->children()
                ->arrayNode('parameters')
                    ->children()
                        ->scalarNode('loginname')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('#password')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('#securitytoken')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->booleanNode('sandbox')
                            ->defaultValue(false)
                        ->end()
                    ->end()
                ->end() // parameters
            ->end()
        ;
        // @formatter:on
        $scaffoldNode->append($extractorNode);
    }
}
