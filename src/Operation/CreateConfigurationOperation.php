<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Keboola\ScaffoldApp\ApiClientStore;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContext;
use Keboola\ScaffoldApp\OperationConfig\CreateConfigurationOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;
use Psr\Log\LoggerInterface;

class CreateConfigurationOperation implements OperationInterface
{
    /**
     * @var ApiClientStore
     */
    private $apiClientStore;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ApiClientStore $apiClientStore, LoggerInterface $logger)
    {
        $this->apiClientStore = $apiClientStore;
        $this->logger = $logger;
    }

    /**
     * @param CreateConfigurationOperationConfig $operationConfig
     */
    public function execute(
        OperationConfigInterface $operationConfig,
        ExecutionContext $executionContext
    ): void {
        $configuration = $operationConfig->getRequestConfiguration();

        $this->logger->info(
            sprintf(
                'Creating configuration for component %s with name %s',
                $configuration->getComponentId(),
                $configuration->getName()
            )
        );

        if (!empty($configuration->getConfiguration())) {
            // encrypt configuration if any
            $tokenInfo = $this->apiClientStore->getStorageApiClient()->verifyToken();
            $configuration->setConfiguration($this->apiClientStore->getEncryptionApiClient()->encryptConfigurationData(
                $configuration->getConfiguration(),
                $configuration->getComponentId(),
                (string) $tokenInfo['owner']['id']
            ));
        }

        $response = $this->apiClientStore->getComponentsApiClient()->addConfiguration($configuration);
        $configuration->setConfigurationId($response['id']);

        $this->logger->info(
            sprintf(
                'Configuration for component %s with id %s created',
                $configuration->getComponentId(),
                $configuration->getConfigurationId()
            )
        );

        $userActions = $operationConfig->getAuthorization()->authorize(
            $this->logger,
            $configuration,
            $this->apiClientStore->getStorageApiClient(),
            $this->apiClientStore->getEncryptionApiClient()
        );
        // save id for use in orchestration
        $executionContext->getOperationsQueue()
            ->finishOperation($operationConfig->getId(), self::class, $configuration, $userActions);
    }
}
