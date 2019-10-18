<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Keboola\ScaffoldApp\OperationConfig\CreateConfigurationOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;

class CreateConfigurationOperation implements OperationInterface
{
    /**
     * @param CreateConfigurationOperationConfig $operationConfig
     */
    public function execute(
        OperationConfigInterface $operationConfig,
        ExecutionContext $executionContext
    ): void {
        $configuration = $operationConfig->getRequestConfiguration();

        $executionContext->getLogger()->info(
            sprintf(
                'Creating configuration for component %s with name %s',
                $configuration->getComponentId(),
                $configuration->getName()
            )
        );

        if (!empty($configuration->getConfiguration())) {
            // encrypt configuration if any
            $tokenInfo = $executionContext->getStorageApiClient()->verifyToken();
            $configuration->setConfiguration($executionContext->getEncryptionApiClient()->encryptConfigurationData(
                $configuration->getConfiguration(),
                $configuration->getComponentId(),
                (string) $tokenInfo['owner']['id']
            ));
        }

        $response = $executionContext->getComponentsApiClient()->addConfiguration($configuration);
        $configuration->setConfigurationId($response['id']);

        $executionContext->getLogger()->info(
            sprintf(
                'Configuration for component %s with id %s created',
                $configuration->getComponentId(),
                $configuration->getConfigurationId()
            )
        );

        $userActions = $operationConfig->getAuthorization()->authorize(
            $executionContext->getLogger(),
            $configuration,
            $executionContext->getStorageApiClient(),
            $executionContext->getEncryptionApiClient()
        );
        // save id for use in orchestration
        $executionContext->finishOperation($operationConfig->getId(), self::class, $configuration, $userActions);
    }
}
