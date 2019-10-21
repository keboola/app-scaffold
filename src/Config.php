<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Keboola\Component\Config\BaseConfig;
use Keboola\Component\Config\BaseConfigDefinition;
use Keboola\ScaffoldApp\SyncActions\ListScaffoldsAction;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Keboola\Component\UserException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class Config extends BaseConfig
{
    public function __construct(
        array $config,
        ?ConfigurationInterface $configDefinition = null
    ) {
        if ($config['action'] === ListScaffoldsAction::NAME) {
            parent::__construct($config, new BaseConfigDefinition);
        } else {
            parent::__construct($config, $configDefinition);
        }
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

        $processor = new Processor();

        try {
            $processedConfig = $processor->processConfiguration(
                new ScaffoldInputsDefinition(),
                [$parsed]
            );
        } catch (InvalidConfigurationException $e) {
            throw new UserException($e->getMessage(), 0, $e);
        }

        return $processedConfig;
    }
}
