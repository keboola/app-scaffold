<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Keboola\ScaffoldApp\OperationConfig\CreateOrchestrationOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;

class CreateOrchestrationOperation implements OperationInterface
{
    /**
     * @param CreateOrchestrationOperationConfig $operationConfig
     */
    public function execute(
        OperationConfigInterface $operationConfig,
        ExecutionContext $executionContext
    ): void {
        $executionContext->getLogger()->info('Creating configuration for orchstration');

        $operationConfig->populateOrchestrationTasksWithConfigurationIds($executionContext);
        $response = $executionContext->getOrchestrationApiClient()->createOrchestration(
            $operationConfig->getOrchestrationName(),
            $operationConfig->getPayload()
        );

        // save id, this for tests
        $executionContext->finishOperation($operationConfig->getId(), self::class, $response['id']);
        $executionContext->getLogger()->info(sprintf('Orchestration %s created', $response['id']));
    }
}
