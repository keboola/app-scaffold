<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Keboola\ScaffoldApp\ApiClientStore;
use Keboola\ScaffoldApp\Operation\UseScaffoldExecutionContext\ExecutionContext;
use Keboola\ScaffoldApp\OperationConfig\CreateOrchestrationOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;

class CreateOrchestrationOperation implements OperationInterface
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
     * @param CreateOrchestrationOperationConfig $operationConfig
     */
    public function execute(
        OperationConfigInterface $operationConfig,
        ExecutionContext $executionContext
    ): void {
        $this->apiClientStore->getLogger()->info('Creating configuration for orchstration');

        $operationConfig->populateOrchestrationTasksWithConfigurationIds($executionContext);
        $response = $this->apiClientStore->getOrchestrationApiClient()->createOrchestration(
            $operationConfig->getOrchestrationName(),
            $operationConfig->getPayload()
        );

        // save id, this for tests
        $executionContext->finishOperation($operationConfig->getId(), self::class, $response['id']);
        $this->apiClientStore->getLogger()->info(sprintf('Orchestration %s created', $response['id']));
    }
}
