<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Keboola\Component\Config\BaseConfig;
use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Processor;

class Config extends BaseConfig
{
    /**
     * @return string
     */
    public function getScaffoldName(): string
    {
        return $this->getParameters()['id'];
    }

    public function getScaffoldInputs(): array
    {
        $scaffoldInputsDefinition = new ScaffoldInputsDefinition($this->getScaffoldName());
        $scaffoldInputsDefinition->getConfigTreeBuilder();
        $processor = new Processor();
        $processedConfig = $processor->processConfiguration($scaffoldInputsDefinition, [$this->getParameters()['inputs']]);
        return $processedConfig;
    }
}
