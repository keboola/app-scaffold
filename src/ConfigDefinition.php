<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Finder\Finder;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        $finder = new Finder();
        $finder->files()->in(__DIR__ . '/../scaffolds/*')->name('ScaffoldDefinition.php');
        foreach ($finder as $file) {
            /** @var ConfigurationInterface $configDefinition */
            $class = 'Keboola\\Scaffolds\\' . $file->getPathInfo()->getBasename() . '\\' . 'ScaffoldDefinition';
            $configDefinition = new $class;
            $parametersNode->append($configDefinition->getConfigTreeBuilder()->getRootNode());
        }
        return $parametersNode;
    }
}
