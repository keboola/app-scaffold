<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\mock;

use Keboola\ScaffoldApp\ScaffoldInputDefinitionInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ScaffoldDefinitionMock implements ScaffoldInputDefinitionInterface
{
    public function addInputsDefinition(
        ArrayNodeDefinition $node
    ): ArrayNodeDefinition {
        $node->children()
            ->arrayNode('ex01')->isRequired()->ignoreExtraKeys(false)->end()
            ->arrayNode('wr01')->isRequired()->ignoreExtraKeys(false)->end()
            ->end();
        return $node;
    }
}
