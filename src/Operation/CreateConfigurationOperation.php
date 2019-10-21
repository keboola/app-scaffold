<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Keboola\ScaffoldApp\ApiClientStore;
use Keboola\ScaffoldApp\Operation\UseScaffoldExecutionContext\ExecutionContext;
use Keboola\ScaffoldApp\OperationConfig\CreateConfigurationOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;

class CreateConfigurationOperation implements OperationInterface
{
    /**
     * @var ApiClientStore
     */
    private $apiClientStore;

    public function __construct(ApiClientStore $apiClientStore)
    {
        $this->apiClientStore = $apiClientStore;
    }

    /**
     * @param CreateConfigurationOperationConfig $operationConfig
     */
    public function execute(
        OperationConfigInterface $operationConfig,
        ExecutionContext $executionContext
    ): void {
        $configuration = $operationConfig->getRequestConfiguration();

        $this->apiClientStore->getLogger()->info(
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

        $this->apiClientStore->getLogger()->info(
            sprintf(
                'Configuration for component %s with id %s created',
                $configuration->getComponentId(),
                $configuration->getConfigurationId()
            )
        );

        $userActions = $operationConfig->getAuthorization()->authorize(
            $this->apiClientStore->getLogger(),
            $configuration,
            $this->apiClientStore->getStorageApiClient(),
            $this->apiClientStore->getEncryptionApiClient()
        );
        // save id for use in orchestration
        $executionContext->finishOperation($operationConfig->getId(), self::class, $configuration, $userActions);
    }
}
