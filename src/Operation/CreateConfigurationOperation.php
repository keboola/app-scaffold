<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Keboola\ScaffoldApp\EncryptionClient;
use Keboola\ScaffoldApp\OperationConfig\CreateConfigurationOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;
use Keboola\StorageApi\Client as StorageClient;
use Keboola\StorageApi\Components;
use Psr\Log\LoggerInterface;

class CreateConfigurationOperation implements OperationInterface
{
    /**
     * @var StorageClient
     */
    private $storageApiClient;

    /**
     * @var EncryptionClient
     */
    private $encryptionApiClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Components
     */
    private $componentsApiClient;

    public function __construct(
        StorageClient $storageApiClient,
        EncryptionClient $encryptionApiClient,
        Components $componentsApiClient,
        LoggerInterface $logger
    ) {
        $this->storageApiClient = $storageApiClient;
        $this->encryptionApiClient = $encryptionApiClient;
        $this->logger = $logger;
        $this->componentsApiClient = $componentsApiClient;
    }

    /**
     * @param CreateConfigurationOperationConfig $operationConfig
     */
    public function execute(
        OperationConfigInterface $operationConfig,
        FinishedOperationsStore $store
    ): void {
        $configuration = $operationConfig->getRequestConfiguration();

        $this->logger->info(
            sprintf(
                'Creating configuration for component %s with name %s',
                $configuration->getComponentId(),
                $configuration->getName()
            )
        );

        $tokenInfo = $this->storageApiClient->verifyToken();
        $configuration->setConfiguration($this->encryptionApiClient->encryptConfigurationData(
            $configuration->getConfiguration(),
            $configuration->getComponentId(),
            (string) $tokenInfo['owner']['id']
        ));
        $response = $this->componentsApiClient->addConfiguration($configuration);
        $configuration->setConfigurationId($response['id']);

        $this->logger->info(
            sprintf(
                'Configuration for component %s with id %s created',
                $configuration->getComponentId(),
                $configuration->getConfigurationId()
            )
        );

        // save id for use in orchestration
        $store->add($operationConfig->getId(), self::class, $configuration);
    }
}
