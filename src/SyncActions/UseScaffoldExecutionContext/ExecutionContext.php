<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext;

use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\Operation\CreateOrchestrationOperation;
use Keboola\ScaffoldApp\Operation\OperationsQueue;
use Keboola\StorageApi\Options\Components\Configuration;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class ExecutionContext
{
    /**
     * @var array
     */
    private $manifest;

    /**
     * @var array
     */
    private $scaffoldInputs;

    /**
     * @var SplFileInfo
     */
    private $scaffoldFolder;

    /**
     * @var string
     */
    private $scaffoldId;

    /**
     * @var OperationsQueue
     */
    private $operationsQueue;

    public function __construct(
        array $manifest,
        array $scaffoldInputs,
        string $scaffoldFolder,
        OperationsQueue $operationsQueue
    ) {
        $this->manifest = $manifest;
        $this->scaffoldInputs = $scaffoldInputs;
        $this->scaffoldFolder = new SplFileInfo($scaffoldFolder, '', '');
        $this->scaffoldId = $this->scaffoldFolder->getFilename();
        $this->operationsQueue = $operationsQueue;
    }

    /**
     * @param mixed $data
     */
    public function finishOperation(
        string $operationId,
        string $operationClass,
        $data,
        array $userActions = []
    ): void {
        $this->operationsQueue->finishOperation($operationId, $operationClass, $data, $userActions);
    }

    /**
     * @return mixed
     */
    public function getFinishedOperationData(string $operationId)
    {
        return $this->operationsQueue->getFinishedOperationData($operationId);
    }

    public function getFinishedOperationsResponse(): array
    {
        $response = [];
        foreach ($this->operationsQueue->getFinishedOperations() as $operation) {
            switch ($operation['operationClass']) {
                case CreateConfigurationOperation::class:
                    /** @var Configuration $data */
                    $data = $operation['data'];
                    $response[] = [
                        'id' => $operation['id'],
                        'configurationId' => $data->getConfigurationId(),
                        'userActions' => $operation['userActions'],
                    ];
                    break;
                case CreateOrchestrationOperation::class:
                    $response[] = [
                        'id' => $operation['id'],
                        'configurationId' => $operation['data'],
                        'userActions' => $operation['userActions'],
                    ];
                    break;
                default:
                    break;
            }
        }

        return $response;
    }

    /**
     * @return array
     */
    public function getOperationsQueue(): array
    {
        return $this->operationsQueue->getOperationsQueue();
    }

    public function getScaffoldDefinitionClass(): ?string
    {
        if ((new Filesystem())->exists(sprintf(
            '%s/ScaffoldDefinition.php',
            $this->scaffoldFolder
        ))) {
            return 'Keboola\\Scaffolds\\' . $this->scaffoldId . '\\ScaffoldDefinition';
        }
        return null;
    }

    public function getScaffoldId(): string
    {
        return $this->scaffoldId;
    }

    /**
     * @return array
     */
    public function getScaffoldInputs(): array
    {
        return $this->scaffoldInputs;
    }

    public function getSchemaForOperation(string $seekOperationId): ?array
    {
        foreach ($this->manifest['inputs'] as $operationInput) {
            if ($operationInput['id'] === $seekOperationId) {
                return empty($operationInput['schema']) ? null : $operationInput['schema'];
            }
        }
        return null;
    }

    public function getUserInputsForOperation(string $seekOperationId): ?array
    {
        foreach ($this->scaffoldInputs as $operationId => $operationInput) {
            if ($operationId === $seekOperationId) {
                return empty($operationInput) ? null : $operationInput;
            }
        }
        return null;
    }
}
