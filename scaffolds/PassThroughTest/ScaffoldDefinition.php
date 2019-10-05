<?php

declare(strict_types=1);

namespace Keboola\Scaffolds\PassThroughTest;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ScaffoldDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        $parameters = new TreeBuilder('snowflakeExtractor');
        $node = $parameters->getRootNode();
        $node->ignoreExtraKeys(false);
        $parametersNode->append($node);

        $parameters = new TreeBuilder('connectionWriter');
        $node = $parameters->getRootNode();
        $node->ignoreExtraKeys(false);
        $parametersNode->append($node);
        return $parametersNode;
    }
}
