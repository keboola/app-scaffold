<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

use Keboola\Orchestrator\OrchestrationTask;
use Keboola\ScaffoldApp\Importer\Decorator\ComponentConfigurationRowsDecorator;
use Keboola\ScaffoldApp\Importer\Decorator\DecoratorInterface;
use Keboola\ScaffoldApp\Importer\Decorator\ParametersClearDecorator;
use Keboola\ScaffoldApp\Importer\Decorator\TransformationConfigurationRowsDecorator;

class OperationImportFactory
{
    private const DECORATORS = [
        TransformationConfigurationRowsDecorator::class,
        ComponentConfigurationRowsDecorator::class,
        ParametersClearDecorator::class,
    ];

    public static function createOperationImport(
        array $configuration,
        OrchestrationTask $task
    ): OperationImport {
        $payload = [
            'name' => $configuration['name'],
            'configuration' => $configuration['configuration'],
        ];

        $operationId = lcfirst(Helper::convertToCamelCase($task->getComponent() . '_' . Helper::generateRandomSufix()));
        $operationImport = new OperationImport(
            $operationId,
            $task->getComponent(),
            $payload,
            $task,
            $configuration['rows']
        );

        foreach (self::DECORATORS as $decorator) {
            /** @var DecoratorInterface $decorator */
            $decorator = new $decorator();
            if ($decorator->supports($operationImport)) {
                $operationImport = $decorator->getDecoratedProjectImport($operationImport);
            }
        }

        return $operationImport;
    }
}
