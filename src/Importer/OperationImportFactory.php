<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

use Keboola\Orchestrator\OrchestrationTask;

class OperationImportFactory
{
    public static function createOperationImport(
        array $configuration,
        OrchestrationTask $task
    ): OperationImport {
        $payload = [
            'name' => $configuration['name'],
            'configuration' => $configuration['configuration'],
        ];
        if (!empty($configuration['processors'])) {
            // copy processors if exists
            $payload['processors'] = $configuration['processors'];
        }

        $operationId = lcfirst(Helper::convertToCamelCase($task->getComponent() . '_' . Helper::generateRandomSufix()));
        $operationImport = new OperationImport(
            $operationId,
            $task->getComponent(),
            $payload,
            $task,
            $configuration['rows']
        );

        return (new OperationImportDecorator($operationImport))->getDecoratedOperationImport();
    }
}
