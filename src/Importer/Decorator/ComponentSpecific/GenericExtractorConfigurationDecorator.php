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
 * Configuration has path
 * "configuration.parameters.config.jobs[].dataType|endPoint" This applies to
 * all components based on generic extractor and generic extractor
 *
 * Jobs can have recursive children[]
 * each dataType is exported table.
 *
 * ## Decorator function:
 *
 * For configuration are added processors:
 * "keboola.processor-create-manifest" "keboola.processor-skip-lines"
 * "keboola.processor-add-metadata".
 *
 * "keboola.processor-add-metadata" has table entry for each dataType|endPoint
 */
class GenericExtractorConfigurationDecorator extends AbstractDecorator
{
    private const GENERIC_EXTRACTOR_COMPONENT_ID = 'keboola.ex-generic-v2';
    private const PROCESSOR_ADD_METADATA_TEMPLATE = [
        'definition' => [
            'component' => 'keboola.processor-add-metadata',
        ],
        'parameters' => [
            'tables' => [],
        ],
    ];

    public function getDecoratedProjectImport(
        OperationImport $operationImport
    ): OperationImport {
        $payload = $operationImport->getPayload();
        $dataTypes = $this->getDataTypesRecursive($payload['configuration']['parameters']['config']['jobs']);
        $isGenericExtractor = $operationImport->getComponentId() === self::GENERIC_EXTRACTOR_COMPONENT_ID;

        $outputBucket = null;
        if (!empty($payload['configuration']['parameters']['config']['outputBucket'])) {
            $outputBucket = $payload['configuration']['parameters']['config']['outputBucket'];
        }

        $addMetadataProcessorTables = [];
        foreach ($dataTypes as $dataType) {
            // simulate configuration id
            $realTableName = sprintf('in.c-%s.%s', $operationImport->getComponentId(), $dataType);
            $metadataValue = $this->convertTableNameToMetadataValue(
                $operationImport,
                $realTableName
            );
            $tableName = $dataType;
            if ($outputBucket !== null) {
                $tableName = sprintf('%s.%s', $outputBucket, $dataType);
            }
            $addMetadataProcessorTables[] = [
                'table' => $tableName,
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

        // add tables to metadata processor
        $processors = [
            self::PROCESSOR_ADD_METADATA_TEMPLATE,
        ];
        $processors[0]['parameters']['tables'] = $addMetadataProcessorTables;
        $afterProcessorKey = DecoratorInterface::USER_ACTION_KEY_PREFIX . '.after';
        if ($isGenericExtractor === true) {
            $afterProcessorKey = 'after';
        }

        if (empty($payload['configuration']['processors'])) {
            $payload['configuration']['processors'] = [];
        }
        if (empty($payload['configuration']['processors']['after'])) {
            $payload['configuration']['processors'][$afterProcessorKey] =
                $processors;
        } else {
            $payload['configuration']['processors'][$afterProcessorKey] =
                array_merge_recursive($payload['configuration']['processors']['after'], $processors);

            if ($afterProcessorKey !== 'after') {
                unset($payload['configuration']['processors']['after']);
            }
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
            } elseif (isset($job['endPoint'])) {
                $dataTypes[] = $job['endPoint'];
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

        if (!isset($payload['configuration']['parameters']['config'])) {
            return false;
        }

        if (empty($payload['configuration']['parameters']['config']['jobs'])) {
            return false;
        }
        $dataTypes = $this->getDataTypesRecursive($payload['configuration']['parameters']['config']['jobs']);

        return !empty($dataTypes);
    }
}
