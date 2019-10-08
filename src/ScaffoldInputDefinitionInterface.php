<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

interface ScaffoldInputDefinitionInterface
{
    public function addInputsDefinition(
        ArrayNodeDefinition $node
    ): ArrayNodeDefinition;
}
