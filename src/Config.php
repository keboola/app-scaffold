<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Keboola\Component\Config\BaseConfig;
use Symfony\Component\Config\Definition\Processor;

class Config extends BaseConfig
{
    public function getScaffoldInputs(): array
    {
        $scaffoldInputsDefinition = new ScaffoldInputsDefinition($this->getScaffoldName());
        $scaffoldInputsDefinition->getConfigTreeBuilder();
        $processor = new Processor();
        $processedConfig = $processor->processConfiguration(
            $scaffoldInputsDefinition,
            [$this->getParsedInputs()]
        );
        return $processedConfig;
    }

    public function getScaffoldName(): string
    {
        return $this->getParameters()['id'];
    }

    public function getParsedInputs(): array
    {
        $parsed = [];
        foreach ($this->getParameters()['inputs'] as $input) {
            $parsed[$input['id']] = $input['values'];
        }

        return $parsed;
    }
}
