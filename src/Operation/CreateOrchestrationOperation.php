<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\ScaffoldApp\OperationConfig\CreateOrchestrationOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;
use Psr\Log\LoggerInterface;

class CreateOrchestrationOperation implements OperationInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrchestratorClient
     */
    private $orchestrationApiClient;

    public function __construct(
        OrchestratorClient $orchestrationApiClient,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->orchestrationApiClient = $orchestrationApiClient;
    }

    /**
     * @param CreateOrchestrationOperationConfig $operationConfig
     */
    public function execute(
        OperationConfigInterface $operationConfig,
        FinishedOperationsStore $store
    ): void {
        $this->logger->info('Creating configuration for orchstration');

        $operationConfig->populateOrchestrationTasksWithConfigurationIds($store);
        $response = $this->orchestrationApiClient->createOrchestration(
            $operationConfig->getOrchestrationName(),
            $operationConfig->getPayload()
        );

        // save id, this for tests
        $store->add($operationConfig->getId(), self::class, $response['id']);
        $this->logger->info(sprintf('Orchestration %s created', $response['id']));
    }
}
