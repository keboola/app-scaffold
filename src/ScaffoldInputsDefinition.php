<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Filesystem\Filesystem;

class ScaffoldInputsDefinition implements ConfigurationInterface
{
    private $scaffoldId;

    public function __construct(string $scaffoldId)
    {
        $this->scaffoldId = $scaffoldId;
    }

    public function getConfigTreeBuilder(): TreeBuilder {
        $builder = new TreeBuilder('inputs', 'array');
        /** @var ArrayNodeDefinition $root */
        $root = $builder->getRootNode();
        $scaffoldDefinitionClass = $this->getScaffoldDefinitionClass($this->scaffoldId);

        if ($scaffoldDefinitionClass === null || !class_exists($scaffoldDefinitionClass)) {
            /** @var ArrayNodeDefinition $node */
            $node->ignoreExtraKeys(false);
            return $builder;
        }

        /** @var ScaffoldInputDefinitionInterface $scaffoldDefinition */
        $scaffoldDefinition = new $scaffoldDefinitionClass;
        /** @var ArrayNodeDefinition $definitionNode */
        $scaffoldDefinition->addInputsDefinition($root);
        return $builder;
    }

    private function getScaffoldDefinitionClass(string $scaffoldId): ?string
    {
        if ((new Filesystem())->exists(__DIR__ . '/../scaffolds/' . $scaffoldId)) {
            return 'Keboola\\Scaffolds\\' . $scaffoldId . '\\ScaffoldDefinition';
        }
        return null;
    }
}
