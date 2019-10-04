<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\OperationConfig;

use Exception;
use Keboola\StorageApi\Options\Components\Configuration;

class CreateComponentConfigurationOperationConfig implements OperationConfigInterface
{
    /** @var string */
    protected $id;

    /** @var string */
    private $KBCComponentId;

    /** @var array */
    private $payload = [];

    /** @var string */
    private $configrationName;

    /**
     * @return CreateComponentConfigurationOperationConfig
     */
    public static function create(array $actionConfig, array $parameters): OperationConfigInterface
    {
        $config = new self();

        if (empty($actionConfig['id'])) {
            throw new Exception('Operation create.configuration missing id or is empty');
        }
        $config->id = $actionConfig['id'];

        if (empty($actionConfig['KBCComponentId'])) {
            throw new Exception('Operation create.configuration missing KBCComponentId or is empty');
        }
        $config->KBCComponentId = $actionConfig['KBCComponentId'];

        if (empty($actionConfig['payload'])) {
            throw new Exception('Operation create.configuration missing payload or is empty');
        }
        $config->payload = $actionConfig['payload'];

        if (empty($config->payload['name'])) {
            throw new Exception('Operation create.configuration payload missing component name or is empty');
        }
        $config->configrationName = $config->payload['name'];

        if (is_array($parameters) && array_key_exists($config->getId(), $parameters)) {
            // actions has parameters merge it with payload configuration
            if (empty($config->payload['configuration'])) {
                $config->payload['configuration'] = [];
            }
            $config->payload['configuration'] = array_merge_recursive(
                $config->payload['configuration'],
                $parameters[$config->getId()]
            );
        }

        return $config;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRequestConfiguration(): Configuration
    {
        $configuration = new Configuration;
        $configuration->setComponentId($this->KBCComponentId);
        $configuration->setName($this->configrationName);

        if (!empty($this->payload['configuration'])) {
            $configuration->setConfiguration($this->payload['configuration']);
        }

        return $configuration;
    }
}
