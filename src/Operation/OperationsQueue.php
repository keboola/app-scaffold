<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Exception;
use Symfony\Component\Finder\SplFileInfo;

class OperationsQueue
{
    /**
     * @var array
     */
    private $operationsQueue = [];

    /**
     * @var array
     */
    private $finishedOperations = [];

    public function addOperation(
        string $operationClass,
        SplFileInfo $operationFile
    ): void {
        $this->operationsQueue[$operationClass][] = $operationFile;
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
        $this->finishedOperations[] = [
            'id' => $operationId,
            'operationClass' => $operationClass,
            'data' => $data,
            'userActions' => $userActions,
        ];
    }

    /**
     * @return mixed
     */
    public function getFinishedOperationData(string $operationId)
    {
        foreach ($this->finishedOperations as $operation) {
            if ($operation['id'] === $operationId) {
                return $operation['data'];
            }
        }

        throw new Exception(sprintf(
            'Operation "%s" was not finished or it\'s wrongly configured.',
            $operationId
        ));
    }

    /**
     * @return array
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
