<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Filesystem\Filesystem;

class ConfigDefinition extends BaseConfigDefinition
{
    /**
     * @var Config|null
     */
    private $generalConfig;

    public function __construct(?Config $generalConfig)
    {
        $this->generalConfig = $generalConfig;
    }

    private function getGeneralDefinition(
        ArrayNodeDefinition $parametersNode,
        ArrayNodeDefinition $scafoldParametersNode
    ): void {
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->children()
                ->arrayNode('scaffolds')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('name')
                                    ->cannotBeEmpty()
                                    ->isRequired()
                                ->end()
                            ->end()
                            ->append($scafoldParametersNode)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        // @formatter:on
    }

    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();

        $scaffoldDefinitionClass = $this->getScaffoldDefinitionClass($this->generalConfig);

        if ($scaffoldDefinitionClass === null) {
            // if no definition class or missing scaffoldName add empty parameters
            // this is used to validate structure before scaffold name is known
            $treeBuilder = new TreeBuilder('parameters');
            /** @var ArrayNodeDefinition $node */
            $node = $treeBuilder->getRootNode();
            $node->ignoreExtraKeys(false);
            $node->isRequired();
            $this->getGeneralDefinition($parametersNode, $node);
            return $parametersNode;
        }

        /** @var ConfigurationInterface $scaffoldDefinition */
        $scaffoldDefinition = new $scaffoldDefinitionClass;
        /** @var ArrayNodeDefinition $definitionNode */
        $definitionNode = $scaffoldDefinition->getConfigTreeBuilder()->getRootNode();
        $this->getGeneralDefinition($parametersNode, $definitionNode);
        return $parametersNode;
    }

    private function getScaffoldDefinitionClass(?Config $generalConfig): ?string
    {
        if ($generalConfig === null) {
            return null;
        }

        if ((new Filesystem())->exists(__DIR__ . '/../scaffolds/' . $generalConfig->getScaffoldName())) {
            return 'Keboola\\Scaffolds\\' . $generalConfig->getScaffoldName() . '\\ScaffoldDefinition';
        }

        return null;
    }
}
