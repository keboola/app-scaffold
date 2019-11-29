<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext;

use Keboola\ScaffoldApp\Operation\OperationsQueue;
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

    public function getFinishedOperationsResponse(): array
    {
        $response = [];
        foreach ($this->operationsQueue->getFinishedOperations()->getIterator() as $operation) {
            if (!$operation->isInResponse()) {
                continue;
            }
            $response[] = $operation->toResponseArray();
        }

        return $response;
    }

    public function getOperationsQueue(): OperationsQueue
    {
        return $this->operationsQueue;
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

    public function getUserInputsForOperation(string $seekOperationId): array
    {
        foreach ($this->scaffoldInputs as $operationId => $operationInput) {
            if ($operationId === $seekOperationId) {
                return $operationInput ?? [];
            }
        }
        return [];
    }

    public function getScaffoldId(): string
    {
        return $this->scaffoldId;
    }

    public function getManifestRequirements(): array
    {
        return isset($this->manifest['requirements']) ? $this->manifest['requirements'] : [];
    }

    public function getManifestOutputs(): array
    {
        return isset($this->manifest['outputs']) ? $this->manifest['outputs'] : [];
    }

    public function getScaffoldFolder(): string
    {
        return $this->scaffoldFolder->getPath();
    }
}
