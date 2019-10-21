<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation\UseScaffoldExecutionContext;

use Symfony\Component\Finder\SplFileInfo;

class OperationsQueue
{
    /**
     * @var array
     */
    private $operationsQueue = [];

    /**
     * @var array|FinishedOperation[]
     */
    private $finishedOperations = [];

    public function addOperation(
        string $operationName,
        SplFileInfo $operationFile
    ): void {
        if (!array_key_exists($operationName, $this->operationsQueue)) {
            $this->operationsQueue[$operationName] = [];
        }

        $this->operationsQueue[$operationName][] = $operationFile;
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
        $this->finishedOperations[] = new FinishedOperation(
            $operationId,
            $operationClass,
            $data,
            $userActions
        );
    }

    /**
     * @return mixed
     */
    public function getFinishedOperationData(string $operationId)
    {
        foreach ($this->finishedOperations as $operation) {
            if ($operation->getOperationId() === $operationId) {
                return $operation->getData();
            }
        }

        throw new \Exception(sprintf(
            'Operation "%s" was not finished or it\'s wrongly configured.',
            $operationId
        ));
    }

    /**
     * @return array|FinishedOperation[]
     */
    public function getFinishedOperations(): array
    {
        return $this->finishedOperations;
    }

    /**
     * @return array
     */
    public function getOperationsQueue(): array
    {
        return $this->operationsQueue;
    }
}
