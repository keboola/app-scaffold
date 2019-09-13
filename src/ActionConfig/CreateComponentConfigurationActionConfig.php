<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\ActionConfig;

use Exception;
use Keboola\StorageApi\Options\Components\Configuration;

class CreateComponentConfigurationActionConfig extends AbstractActionConfig
{
    /** @var string */
    private $KBCComponentId;

    /** @var bool */
    private $saveConfigId = false;

    /** @var array */
    private $payload = [];

    /** @var string */
    private $configrationName;

    /**
     * @return CreateComponentConfigurationActionConfig
     */
    public static function create(array $actionConfig, ?array $parameters): ActionConfigInterface
    {
        $config = new self();

        if (array_key_exists('id', $actionConfig)) {
            $config->id = $actionConfig['id'];
        }
        if (array_key_exists('KBCComponentId', $actionConfig)) {
            $config->KBCComponentId = $actionConfig['KBCComponentId'];
        } else {
            throw new Exception('Actions create.configuration missing KBCComponentId');
        }
        if (array_key_exists('saveConfigId', $actionConfig)) {
            $config->saveConfigId = (bool) $actionConfig['saveConfigId'];
        }
        if (array_key_exists('payload', $actionConfig)) {
            $config->payload = $actionConfig['payload'];
        } else {
            throw new Exception('Actions create.configuration missing payload');
        }
        if (array_key_exists('name', $config->payload)) {
            $config->configrationName = $config->payload['name'];
        } else {
            throw new Exception('Actions create.configuration payload missing component name');
        }

        if (is_array($parameters) && $config->getId() !== null && array_key_exists($config->getId(), $parameters)) {
            // actions has parameters merge it with paylod configuration
            if (!array_key_exists('configuration', $config->payload)) {
                $config->payload['configuration'] = [];
            }
            $config->payload['configuration'] = array_merge_recursive(
                $config->payload['configuration'],
                $parameters[$config->getId()]
            );
        }

        return $config;
    }

    public function isSaveConfigId(): bool
    {
        return $this->saveConfigId;
    }

    public function getRequestConfiguration(): Configuration
    {
        $configuration = new Configuration;
        $configuration->setComponentId($this->KBCComponentId);
        $configuration->setName($this->configrationName);

        if (array_key_exists('configuration', $this->payload)) {
            $configuration->setConfiguration($this->payload['configuration']);
        }

        return $configuration;
    }
}
