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
    public static function create(
        array $actionConfig,
        array $parameters
    ): OperationConfigInterface {
        $config = new self();

        if (empty($actionConfig['id'])) {
            throw new Exception(sprintf(
                'Operation ID is missing in create.configuration operation "%s".',
                json_encode($actionConfig)
            ));
        }
        $config->id = $actionConfig['id'];

        if (empty($actionConfig['KBCComponentId'])) {
            throw new Exception(sprintf(
                'Component Id is missing in operation create.configuration with id "%s".',
                $config->getId()
            ));
        }
        $config->KBCComponentId = $actionConfig['KBCComponentId'];

        if (empty($actionConfig['payload'])) {
            throw new Exception(sprintf(
                'Configuration payload is missing in operation create.configuration with id "%s".',
                $config->getId()
            ));
        }
        $config->payload = $actionConfig['payload'];

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
                ['parameters' => $parameters[$config->getId()]]
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
