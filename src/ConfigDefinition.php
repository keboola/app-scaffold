<?php

declare(strict_types=1);

namespace MyComponent;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode->append($this->getScaffoldCrmMrrSalesforceNode());
        // @formatter:on
        return $parametersNode;
    }

    private function getScaffoldCrmMrrSalesforceNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('scaffold_crm_mrr_salesforce');
        /** @var ArrayNodeDefinition $scaffoldNode */
        $scaffoldNode = $treeBuilder->getRootNode();

        // @formatter:off
        $treeBuilder = new TreeBuilder('htns#ex_salesforce');
        /** @var ArrayNodeDefinition $node */
        $node = $treeBuilder->getRootNode();
        $node->children()
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
                    ->defaultFalse()
                ->end()
            ->end()
        ;
        $scaffoldNode->append($node);

        $treeBuilder = new TreeBuilder('keboola#wr_db_snowflake');
        /** @var ArrayNodeDefinition $node */
        $node = $treeBuilder->getRootNode();
        $node
            ->children()
                ->arrayNode('parameters')
                    ->children()
                        ->arrayNode('db')
                            ->children()
                                ->scalarNode('driver')->end()
                                ->scalarNode('host')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('port')->end()
                                ->scalarNode('warehouse')->end()
                                ->scalarNode('database')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('schema')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('user')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('password')->end()
                                ->scalarNode('#password')->end()
                                ->append($this->addSshNode())
                            ->end()
                        ->end() // db
                    ->end()
                ->end() // parameters
            ->end()
        ;
        $scaffoldNode->append($node);
        // @formatter:on
        return $scaffoldNode;
    }

    public function addSshNode(): NodeDefinition
    {
        $builder = new TreeBuilder('ssh');
        /** @var ArrayNodeDefinition $node */
        $node = $builder->getRootNode();
        // @formatter:off
        $node
            ->children()
                ->booleanNode('enabled')->end()
                ->arrayNode('keys')
                    ->children()
                        ->scalarNode('private')->end()
                        ->scalarNode('#private')->end()
                        ->scalarNode('public')->end()
                    ->end()
                ->end()
                ->scalarNode('sshHost')->end()
                ->scalarNode('sshPort')
                    ->defaultValue('22')
                ->end()
                    ->scalarNode('remoteHost')
                ->end()
                    ->scalarNode('remotePort')
                ->end()
                ->scalarNode('localPort')
                    ->defaultValue('33006')
                ->end()
                ->scalarNode('user')->end()
            ->end();
        // @formatter:on
        return $node;
    }
}
