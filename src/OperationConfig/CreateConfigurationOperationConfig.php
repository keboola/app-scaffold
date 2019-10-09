<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\OperationConfig;

use Exception;
use Keboola\StorageApi\Options\Components\Configuration;

class CreateConfigurationOperationConfig implements OperationConfigInterface
{
    /** @var string */
    protected $id;

    /** @var string */
    private $componentId;

    /** @var array */
    private $payload = [];

    /** @var string */
    private $configrationName;

    /**
     * @return CreateConfigurationOperationConfig
     */
    public static function create(
        string $operationId,
        array $operationConfig,
        array $parameters
    ): OperationConfigInterface {
        $config = new self();

        $config->id = $operationId;

        if (empty($operationConfig['componentId'])) {
            throw new Exception(sprintf(
                'Component Id is missing in operation create.configuration with id "%s".',
                $config->getId()
            ));
        }
        $config->componentId = $operationConfig['componentId'];

        if (empty($operationConfig['payload'])) {
            throw new Exception(sprintf(
                'Configuration payload is missing in operation create.configuration with id "%s".',
                $config->getId()
            ));
        }
        $config->payload = $operationConfig['payload'];

        if (empty($config->payload['name'])) {
            throw new Exception(sprintf(
                'Configuration name is missing in operation create.configuration payload with id "%s".',
                $config->getId()
            ));
        }
        $config->configrationName = $config->payload['name'];

        if (is_array($parameters) && isset($parameters[$config->getId()])) {
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
        $configuration->setComponentId($this->componentId);
        $configuration->setName($this->configrationName);

        if (!empty($this->payload['configuration'])) {
            $configuration->setConfiguration($this->payload['configuration']);
        } else {
            $configuration->setConfiguration([]);
        }

        return $configuration;
    }
}