<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Keboola\Component\Config\BaseConfigDefinition;
use Keboola\Scaffolds\ReviewsReviewTrackers\ScaffoldDefinition;
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
        ArrayNodeDefinition $scaffoldParametersNode
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
                            ->append($scaffoldParametersNode)
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

        if ($scaffoldDefinitionClass === null || !class_exists($scaffoldDefinitionClass)) {
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

        /** @var ScaffoldDefinition $scaffoldDefinition */
        $scaffoldDefinition = new $scaffoldDefinitionClass;
        /** @var ArrayNodeDefinition $definitionNode */
        $definitionNode = $scaffoldDefinition->getParametersDefinition();
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
