<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

use Keboola\Orchestrator\OrchestrationTask;
use Keboola\ScaffoldApp\Importer\Decorator\ComponentSpecific\ExSalesforceConfigurationRowsDecorator;
use Keboola\ScaffoldApp\Importer\Decorator\ComponentSpecific\GenericExtractorConfigurationDecorator;
use Keboola\ScaffoldApp\Importer\Decorator\DecoratorInterface;
use Keboola\ScaffoldApp\Importer\Decorator\ClearEncryptedParametersDecorator;
use Keboola\ScaffoldApp\Importer\Decorator\StorageInputTablesDecorator;
use Keboola\ScaffoldApp\Importer\Decorator\TransformationConfigurationRowsDecorator;
use Symfony\Component\Console\Output\OutputInterface;

class OperationImportFactory
{
    private const DECORATORS = [
        TransformationConfigurationRowsDecorator::class,
        ExSalesforceConfigurationRowsDecorator::class,
        ClearEncryptedParametersDecorator::class,
        StorageInputTablesDecorator::class,
        GenericExtractorConfigurationDecorator::class,
    ];

    public static function createOperationImport(
        array $configuration,
        OrchestrationTask $task,
        string $scaffoldId,
        OutputInterface $output
    ): OperationImport {
        $payload = [
            'name' => $configuration['name'],
            'configuration' => $configuration['configuration'],
        ];

        $operationId = CamelCaseConverterHelper::convertToCamelCase(
            $task->getComponent() . '-' . $configuration['name'],
            CamelCaseConverterHelper::STOP_WORDS_NO_UNDERSCORE
        );
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
            $decorator = new $decorator($output);
            if ($decorator->supports($operationImport)) {
                $operationImport = $decorator->getDecoratedProjectImport($operationImport);
            }
        }

        return $operationImport;
    }
}
