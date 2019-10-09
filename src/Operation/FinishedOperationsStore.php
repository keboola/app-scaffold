<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Exception;
use Keboola\StorageApi\Options\Components\Configuration;

class FinishedOperationsStore
{
    /** @var array */
    private $operations = [];

    /**
     * @param mixed $data
     */
    public function add(
        string $operationId,
        string $operationClass,
        $data
    ): void {
        $this->operations[] = [
            'id' => $operationId,
            'operationClass' => $operationClass,
            'data' => $data,
        ];
    }

    /**
     * @return mixed
     */
    public function getOperationData(string $operationId)
    {
        foreach ($this->operations as $operation) {
            if ($operation['id'] === $operationId) {
                return $operation['data'];
            }
        }

        throw new Exception(sprintf(
            'Operation "%s" was not finished or it\'s wrongly configured.',
            $operationId
        ));
    }

    public function getOperations(): array
    {
        return $this->operations;
    }

    public function getOperationsResponse(): array
    {
        $response = [];
        foreach ($this->operations as $operation) {
            switch ($operation['operationClass']) {
                case CreateConfigurationOperation::class:
                    /** @var Configuration $data */
                    $data = $operation['data'];
                    $response[] = [
                        'id' => $operation['id'],
                        'componentId' => $data->getConfigurationId(),
                    ];
                    break;
                case CreateOrchestrationOperation::class:
                    $response[] = [
                        'id' => $operation['id'],
                        'componentId' => $operation['data'],
                    ];
                    break;
                default:
                    break;
            }
        }

        return $response;
    }
}
