<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\SyncActions;

use Keboola\Component\JsonHelper;
use Keboola\ScaffoldApp\ApiClientStore;
use Keboola\ScaffoldApp\Operation\UseScaffoldExecutionContext\ExecutionContext;
use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\Operation\CreateConfigurationRowsOperation;
use Keboola\ScaffoldApp\Operation\CreateOrchestrationOperation;
use Keboola\ScaffoldApp\Operation\OperationsConfig;
use Keboola\ScaffoldApp\OperationConfig\CreateCofigurationRowsOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\CreateConfigurationOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\CreateOrchestrationOperationConfig;
use Symfony\Component\Finder\SplFileInfo;

class UseScaffoldAction
{
    public const NAME = 'useScaffold';

    /**
     * @var ExecutionContext
     */
    private $executionContext;

    /**
     * @var ApiClientStore
     */
    private $apiClientStore;

    public function __construct(ExecutionContext $executionContext, ApiClientStore $apiClientStore)
    {
        $this->executionContext = $executionContext;
        $this->apiClientStore = $apiClientStore;
    }

    public function __invoke(): array
    {
        $queue = $this->executionContext->getOperationsQueue();

        foreach ($queue as $operationName => $operationsFiles) {
            /** @var SplFileInfo $operationFile */
            foreach ($operationsFiles as $operationFile) {
                $this->executeOperation($operationName, $operationFile);
            }
        }

        return $this->executionContext->getFinishedOperationsResponse();
    }

    private function executeOperation(
        string $operationName,
        SplFileInfo $operationFile
    ): void {
        switch ($operationName) {
            case OperationsConfig::CREATE_CONFIGURATION:
                $config = CreateConfigurationOperationConfig::create(
                    $operationFile->getBasename('.json'),
                    JsonHelper::decode($operationFile->getContents()),
                    $this->executionContext->getScaffoldInputs()
                );
                $operation = new CreateConfigurationOperation($this->apiClientStore);
                $operation->execute($config, $this->executionContext);
                break;
            case OperationsConfig::CREATE_CONFIGURATION_ROWS:
                $config = CreateCofigurationRowsOperationConfig::create(
                    $operationFile->getBasename('.json'),
                    JsonHelper::decode($operationFile->getContents()),
                    []
                );
                $operation = new CreateConfigurationRowsOperation($this->apiClientStore);
                $operation->execute($config, $this->executionContext);
                break;
            case OperationsConfig::CREATE_ORCHESTREATION:
                $config = CreateOrchestrationOperationConfig::create(
                    $operationFile->getBasename('.json'),
                    JsonHelper::decode($operationFile->getContents()),
                    []
                );

                $operation = new CreateOrchestrationOperation($this->apiClientStore);
                $operation->execute($config, $this->executionContext);
                break;
        }
    }
}
