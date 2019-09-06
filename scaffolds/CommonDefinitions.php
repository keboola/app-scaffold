<?php

declare(strict_types=1);

namespace Scaffolds;

use MyComponent\ConfigDefinition as MainConfigDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class CommonDefinitions
{
    public static function getSnowflakeWriterConfig(TreeBuilder $treeBuilder): NodeDefinition
    {
        // @formatter:off
        $node = $treeBuilder->getRootNode();
        $node->setPathSeparator(MainConfigDefinition::PATH_SEPARATOR);
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
                                ->append(self::getSshNodeConfig())
                            ->end()
                        ->end() // db
                    ->end()
                ->end() // parameters
            ->end()
        ;
        // @formatter:on
        return $node;
    }

    public static function getSshNodeConfig(): NodeDefinition
    {
        $builder = new TreeBuilder('ssh');
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
