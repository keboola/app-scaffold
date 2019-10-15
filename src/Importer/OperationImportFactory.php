<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

use Keboola\Orchestrator\OrchestrationTask;
use Keboola\ScaffoldApp\Importer\Decorator\ComponentSpecific\ExSalesforceConfigurationRowsDecorator;
use Keboola\ScaffoldApp\Importer\Decorator\DecoratorInterface;
use Keboola\ScaffoldApp\Importer\Decorator\ClearEncryptedParametersDecorator;
use Keboola\ScaffoldApp\Importer\Decorator\TransformationConfigurationRowsDecorator;

class OperationImportFactory
{
    private const DECORATORS = [
        TransformationConfigurationRowsDecorator::class,
        ExSalesforceConfigurationRowsDecorator::class,
        ClearEncryptedParametersDecorator::class,
    ];

    public static function createOperationImport(
        array $configuration,
        OrchestrationTask $task,
        string $scaffoldId
    ): OperationImport {
        $payload = [
            'name' => $configuration['name'],
            'configuration' => $configuration['configuration'],
        ];

        $operationId = lcfirst(TableNameConverterHelper::convertToCamelCase(
            $task->getComponent() . '-' . $configuration['name']
        ));
        $operationImport = new OperationImport(
            $scaffoldId,
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
