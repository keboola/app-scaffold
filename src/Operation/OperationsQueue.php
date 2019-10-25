<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use ArrayObject;
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

    public function __construct()
    {
        $this->finishedOperations = new ArrayObject([], ArrayObject::STD_PROP_LIST);
        $this->operationsQueue = new ArrayObject([], ArrayObject::STD_PROP_LIST);
    }

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
        $this->finishedOperations[$operationId] = [
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
        if (empty($this->finishedOperations[$operationId])) {
            throw new Exception(sprintf(
                'Operation "%s" was not finished or it\'s wrongly configured.',
                $operationId
            ));
        }
        return $this->finishedOperations[$operationId]['data'];
    }

    public function getFinishedOperations(): ArrayObject
    {
        return $this->finishedOperations;
    }

    public function getOperationsQueue(): ArrayObject
    {
        return $this->operationsQueue;
    }
}
