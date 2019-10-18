<?php

declare(strict_types=1);

namespace Keboola\Scaffolds\CrmMrrSalesforce;

use Keboola\ScaffoldApp\ScaffoldInputDefinitionInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ScaffoldDefinition implements ScaffoldInputDefinitionInterface
{
    public function addInputsDefinition(
        ArrayNodeDefinition $node
    ): ArrayNodeDefinition {
        // Add salesforce extractor
        $this->appendSalesForceExtractor($node);
        $this->appendOperationWithoutSchema($node, 'keboolaWrDbSnowflakeLooker', true);
        $this->appendOperationWithoutSchema($node, 'transformationSalesforceCRM&MRR', true);
        $this->appendOperationWithoutSchema($node, 'orchestrationMRR', true);
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

    private function appendOperationWithoutSchema(
        ArrayNodeDefinition $scaffoldNode,
        string $operationId,
        bool $isRequired
    ): void {
        $treeBuilder = new TreeBuilder($operationId);
        /** @var ArrayNodeDefinition $operationNode */
        $operationNode = $treeBuilder->getRootNode();
        if ($isRequired === true) {
            $operationNode->isRequired();
        }
        $scaffoldNode->append($operationNode);
    }
}
