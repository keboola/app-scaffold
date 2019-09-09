<?php

declare(strict_types=1);

namespace MyComponent;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Finder\Finder;

class ConfigDefinition extends BaseConfigDefinition
{
    public const PATH_SEPARATOR = '#';

    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();

        $finder = new Finder();
        $finder->files()->in(__DIR__.'/../scaffolds/*')->name('ConfigDefinition.php');

        foreach ($finder as $file) {
            /** @var ConfigurationInterface $configDefinition */
            $class = 'Scaffolds\\'.$file->getPathInfo()->getBasename().'\\'.'ConfigDefinition';
            $configDefinition = new $class;
            $parametersNode->append($configDefinition->getConfigTreeBuilder()->getRootNode());
        }

        return $parametersNode;
    }
}
