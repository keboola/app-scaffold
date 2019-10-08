<?php

declare(strict_types=1);

namespace Keboola\Scaffolds\PassThroughTest;

use Keboola\ScaffoldApp\ScaffoldInputDefinitionInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ScaffoldDefinition implements ScaffoldInputDefinitionInterface
{
    public function addInputsDefinition(
        ArrayNodeDefinition $node
    ): ArrayNodeDefinition {
        $node->children()
            ->arrayNode('snowflakeExtractor')->ignoreExtraKeys(false)->end()
            ->arrayNode('connectionWriter')->ignoreExtraKeys(false)->end()
            ->end();
        return $node;
    }
}
