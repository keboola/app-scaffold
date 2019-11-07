<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer\Decorator\ComponentSpecific;

use Keboola\ScaffoldApp\Importer\Decorator\AbstractDecorator;
use Keboola\ScaffoldApp\Importer\Decorator\DecoratorInterface;
use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\OrchestrationImporter;

/**
 * # Use case:
 *
 * ## Configuration specification:
 *
 * Configuration has path "configuration.parameters.jobs[].dataType"
 *
 * Jobs can have recursive children[]
 * each dataType is exported table.
 *
 * ## Decorator function:
 *
 * For configuration are added processors:
 * "keboola.processor-create-manifest" "keboola.processor-skip-lines" "keboola.processor-add-metadata".
 *
 * "keboola.processor-add-metadata" has table entry for each dataType
 */
class GenericExtractorConfigurationDecorator extends AbstractDecorator
{
    private const AFTER_PROCESSORS_TEMPLATE = [
        [
            'definition' => [
                'component' => 'keboola.processor-create-manifest',
            ],
            'parameters' => [
                'delimiter' => ',',
                'enclosure' => '"',
                'incremental' => false,
                'primary_key' => [],
                'columns_from' => 'header',
            ],
        ],
        [
            'definition' => [
                'component' => 'keboola.processor-skip-lines',
            ],
            'parameters' => [
                'lines' => 1,
                'direction_from' => 'top',
            ],
        ],
        [
            'definition' => [
                'component' => 'keboola.processor-add-metadata',
            ],
            'parameters' => [
                'tables' => [],
            ],
        ],
    ];

    public function getDecoratedProjectImport(
        OperationImport $operationImport
    ): OperationImport {
        $payload = $operationImport->getPayload();
        $dataTypes = $this->getDataTypesRecursive($payload['configuration']['parameters']['jobs']);

        // add tables to metadata processor
        $processors = self::AFTER_PROCESSORS_TEMPLATE;
        foreach ($dataTypes as $dataType) {
            // simulate configuration id
            $realTableName = sprintf('in.c-%s.%s', $operationImport->getComponentId(), $dataType);
            $metadataValue = $this->convertTableNameToMetadataValue(
                $operationImport,
                $realTableName
            );
            $processors[2]['parameters']['tables'][] = [
                'table' => $dataType,
                'metadata' => [
                    [
                        'key' => OrchestrationImporter::SCAFFOLD_TABLE_TAG,
                        'value' => $metadataValue,
                    ],
                    [
                        'key' => DecoratorInterface::SCAFFOLD_ID_TAG,
                        'value' => $operationImport->getScaffoldId(),
                    ],
                ],
            ];
        }

        if (empty($payload['configuration']['processors'])) {
            $payload['configuration']['processors'] = [];
        }
        if (empty($payload['configuration']['processors']['after'])) {
            $payload['configuration']['processors']['after'] = $processors;
        } else {
            $payload['configuration']['processors']['after'] =
                array_merge_recursive($payload['configuration']['processors']['after'], $processors);
        }

        $this->output->writeln(sprintf(
            'If multiple components "%s" are used don\'t forget to make their tags unique.',
            $operationImport->getComponentId()
        ));

        return new OperationImport(
            $operationImport->getScaffoldId(),
            $operationImport->getOperationId(),
            $operationImport->getComponentId(),
            $payload,
            $operationImport->getTask(),
            $operationImport->getConfigurationRows()
        );
    }

    private function getDataTypesRecursive(array $jobs): array
    {
        $dataTypes = [];
        foreach ($jobs as $job) {
            if (isset($job['dataType'])) {
                $dataTypes[] = $job['dataType'];
            }
            if (!empty($job['children'])) {
                $dataTypes = array_merge(
                    $dataTypes,
                    $this->getDataTypesRecursive($job['children'])
                );
            }
        }
        return $dataTypes;
    }

    public function supports(OperationImport $operationImport): bool
    {
        $payload = $operationImport->getPayload();
        if (!isset($payload['configuration'])) {
            return false;
        }

        if (!isset($payload['configuration']['parameters'])) {
            return false;
        }

        if (empty($payload['configuration']['parameters']['jobs'])) {
            return false;
        }
        $dataTypes = $this->getDataTypesRecursive($payload['configuration']['parameters']['jobs']);

        return !empty($dataTypes);
    }
}
