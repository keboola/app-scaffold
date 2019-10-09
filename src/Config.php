<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Keboola\Component\Config\BaseConfig;
use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

class Config extends BaseConfig
{
    public function __construct(
        array $config,
        ?ConfigurationInterface $configDefinition = null
    ) {
        if ($config['action'] === Component::SYNC_ACTION_LIST_SCAFFOLDS) {
            parent::__construct($config, new BaseConfigDefinition);
        } else {
            parent::__construct($config, $configDefinition);
        }
    }

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
