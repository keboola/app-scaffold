<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ScaffoldInputsDefinition implements ConfigurationInterface
{
    /**
     * @var ScaffoldInputDefinitionInterface
     */
    private $scaffoldDefinitionClass;

    public function __construct(
        ?ScaffoldInputDefinitionInterface $scaffoldDefinitionClass = null
    ) {
        $this->scaffoldDefinitionClass = $scaffoldDefinitionClass;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('inputs', 'array');
        /** @var ArrayNodeDefinition $root */
        $root = $builder->getRootNode();
        $root->isRequired();

        if ($this->scaffoldDefinitionClass === null) {
            /** @var ArrayNodeDefinition $root */
            $root->ignoreExtraKeys(false);
            return $builder;
        }
        /** @var ArrayNodeDefinition $definitionNode */
        $this->scaffoldDefinitionClass->addInputsDefinition($root);
        return $builder;
    }
}
