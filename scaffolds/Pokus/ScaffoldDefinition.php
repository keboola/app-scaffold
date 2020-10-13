<?php

declare(strict_types=1);

namespace Keboola\Scaffolds\Pokus;

use Keboola\ScaffoldApp\ScaffoldInputDefinitionInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ScaffoldDefinition implements ScaffoldInputDefinitionInterface
{
    public function addInputsDefinition(
        ArrayNodeDefinition $node
    ): ArrayNodeDefinition {
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $node->ignoreExtraKeys(false)
            ->end();
        // @formatter:on
        return $node;
    }
}
