<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->children()
                ->scalarNode('id')->isRequired()->cannotBeEmpty()->end()
                ->arrayNode('inputs')
                    ->requiresAtLeastOneElement()
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('id')->isRequired()->cannotBeEmpty()->end()
                            ->arrayNode('values')->ignoreExtraKeys(false)->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
        // @formatter:on
        return $parametersNode;
    }
}
