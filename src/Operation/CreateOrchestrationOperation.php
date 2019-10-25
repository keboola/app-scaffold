<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Keboola\ScaffoldApp\ApiClientStore;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContext;
use Keboola\ScaffoldApp\OperationConfig\CreateOrchestrationOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;
use Psr\Log\LoggerInterface;

class CreateOrchestrationOperation implements OperationInterface
{
    /**
     * @var ApiClientStore
     */
    private $apiClientStore;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ApiClientStore $apiClientStore,
        LoggerInterface $logger
    ) {
        $this->apiClientStore = $apiClientStore;
        $this->logger = $logger;
    }

    /**
     * @param CreateOrchestrationOperationConfig $operationConfig
     */
    public function execute(
        OperationConfigInterface $operationConfig,
        ExecutionContext $executionContext
    ): void {
        $this->logger->info('Creating configuration for orchstration');

        $operationConfig->populateOrchestrationTasksWithConfigurationIds($executionContext);
        $response = $this->apiClientStore->getOrchestrationApiClient()->createOrchestration(
            $operationConfig->getOrchestrationName(),
            $operationConfig->getPayload()
        );

        // save id, this for tests
        $executionContext->getOperationsQueue()
            ->finishOperation($operationConfig->getId(), self::class, $response['id']);
        $this->logger->info(sprintf('Orchestration %s created', $response['id']));
    }
}
