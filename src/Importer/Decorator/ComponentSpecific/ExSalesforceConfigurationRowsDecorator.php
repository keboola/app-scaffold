<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer\Decorator\ComponentSpecific;

use Keboola\ScaffoldApp\Importer\Decorator\DecoratorInterface;
use Keboola\ScaffoldApp\Importer\TableNameConverterHelper;
use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\OrchestrationImporter;

/**
 * # Use case:
 *
 * ## Configuration rows specification:
 *
 * Configuration rows has path "configuration.parameters.objects[]"
 *
 * Each object has name properties "name" and "soql"
 * where name is table name which is exported.
 *
 * ## Decorator function:
 *
 * For each configuration row with object are added two processors:
 * "keboola.processor-create-manifest" "keboola.processor-add-metadata"
 * after processors are prefixed for control.
 *
 */
class ExSalesforceConfigurationRowsDecorator implements DecoratorInterface
{
    private const AFTER_PROCESSORS_TEMPLATE =
        [
            [
                'definition' =>
                    [
                        'component' => 'keboola.processor-create-manifest',
                    ],
                'parameters' =>
                    [
                        'delimiter' => ',',
                        'enclosure' => '"',
                        'incremental' => false,
                        'primary_key' =>
                            [
                            ],
                        'columns_from' => 'header',
                    ],
            ],
            [
                'definition' =>
                    [
                        'component' => 'keboola.processor-add-metadata',
                    ],
                'parameters' =>
                    [
                        'tables' =>
                            [
                            ],
                    ],
            ],
        ];
    private const SUPPORTED_COMPONENTS = ['htns.ex-salesforce'];

    public function getDecoratedProjectImport(
        OperationImport $operationImport
    ): OperationImport {
        $decoratedRows = [];
        foreach ($operationImport->getConfigurationRows() as $row) {
            $decoratedRows[] = $this->getDecoratedComponentConfigurationRow($row, $operationImport);
        }

        return new OperationImport(
            $operationImport->getScaffoldId(),
            $operationImport->getOperationId(),
            $operationImport->getComponentId(),
            $operationImport->getPayload(),
            $operationImport->getTask(),
            $decoratedRows
        );
    }

    private function getDecoratedComponentConfigurationRow(
        array $row,
        OperationImport $operationImport
    ): array {
        // check parameters object support
        if ($this->hasConfigurationRowParametersObject($row)) {
            return $row;
        }

        $possibleTablesNames = [];
        // extract table names
        foreach ($row['configuration']['parameters']['objects'] as &$object) {
            if (empty($object['name'])) {
                continue;
            }
            $possibleTablesNames[] = $object['name'];
        }

        if (0 === count($possibleTablesNames)) {
            // no tables
            return $row;
        }

        // add tables to metadata processor
        $processors = self::AFTER_PROCESSORS_TEMPLATE;
        foreach ($possibleTablesNames as $tableName) {
            $metadataValue = TableNameConverterHelper::convertTableNameForMetadata($operationImport, $tableName);
            $processors[1]['parameters']['tables'][] = [
                'table' => $tableName,
                'metadata' =>
                    [
                        [
                            'key' => OrchestrationImporter::SCAFFOLD_TABLE_TAG,
                            'value' => $metadataValue,
                        ],
                    ],
            ];
        }

        if (empty($row['configuration']['processors'])) {
            $row['configuration']['processors'] = [];
        }
        if (empty($row['configuration']['processors']['after'])) {
            $row['configuration']['processors']['after'] = [];
        }

        $afterProcessors = array_merge_recursive($row['configuration']['processors']['after'], $processors);
        $row['configuration']['processors'][self::USER_ACTION_KEY_PREFIX . '.after'] = $afterProcessors;
        unset($row['configuration']['processors']['after']);

        return $row;
    }

    private function hasConfigurationRowParametersObject(array $row): bool
    {
        return empty($row['configuration']['parameters'])
            || empty($row['configuration']['parameters']['objects']);
    }

    public function supports(OperationImport $operationImport): bool
    {
        if (0 === count($operationImport->getConfigurationRows())) {
            return false;
        }
        return in_array($operationImport->getComponentId(), self::SUPPORTED_COMPONENTS);
    }
}
