<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

class OperationImportDecorator
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
    public const USER_ACTION_KEY_PREFIX = '__SCAFFOLD_CHECK__';

    /**
     * @var OperationImport
     */
    private $operationImport;

    public function __construct(OperationImport $operationImport)
    {
        $this->operationImport = $operationImport;
    }

    public function getDecoratedOperationImport(): OperationImport
    {
        return new OperationImport(
            $this->operationImport->getOperationId(),
            $this->operationImport->getComponentId(),
            $this->operationImport->getPayload(),
            $this->operationImport->getTask(),
            $this->getDecoratedConfigurationRows()
        );
    }

    private function getDecoratedConfigurationRows(): array
    {
        $configurationRows = $this->operationImport->getConfigurationRows();
        if (0 === count($configurationRows)) {
            return $configurationRows;
        }

        if ($this->isOperationTransformation()) {
            // decorate transformations
            $decoratedRows = [];
            foreach ($configurationRows as $row) {
                $decoratedRows[] = $this->getDecoratedTransformationConfigurationRow($row);
            }
            return $decoratedRows;
        }

        $decoratedRows = [];
        foreach ($configurationRows as $row) {
            $decoratedRows[] = $this->getDecoratedComponentConfigurationRow($row);
        }
        return $decoratedRows;
    }

    private function isOperationTransformation(): bool
    {
        return $this->operationImport->getComponentId() === 'transformation';
    }

    private function getDecoratedTransformationConfigurationRow(
        array $row
    ): array {
        $row = $this->addOutputMetadata($row);
        $row = $this->addInputSourceSearch($row);

        return $row;
    }

    private function addOutputMetadata(array $row): array
    {
        foreach ($row['configuration']['output'] as &$output) {
            if (!empty($output['destination'])) {
                if (!isset($output['metadata'])) {
                    $output['metadata'] = [];
                }
                $output['metadata'][] = [
                    'key' => OrchestrationImporter::SCAFFOLD_TABLE_TAG,
                    'value' => sprintf(
                        '%s.%s.%s',
                        OrchestrationImporter::SCAFFOLD_VALUE_PREFIX,
                        $this->operationImport->getOperationId(),
                        $output['destination']
                    ),
                ];
            }
        }

        return $row;
    }

    private function addInputSourceSearch(array $row): array
    {
        foreach ($row['configuration']['input'] as &$input) {
            if (!empty($input['source'])) {
                $input['source_search'] = [
                    // key is annotated with "!@" to notify user that this needs to be checked
                    // as we don't know where is origin of source
                    'key' => OrchestrationImporter::SCAFFOLD_TABLE_TAG,
                    self::USER_ACTION_KEY_PREFIX . '.value' => sprintf(
                        '%s.__change_operation_id__.%s',
                        OrchestrationImporter::SCAFFOLD_VALUE_PREFIX,
                        $input['source']
                    ),
                ];

                // remove source, leave original source with prefix to user for check
                $input[self::USER_ACTION_KEY_PREFIX . '.source'] = $input['source'];
                unset($input['source']);
            }
        }

        return $row;
    }

    private function getDecoratedComponentConfigurationRow(array $row): array
    {
        // check parameters object support
        if (empty($row['configuration']['parameters'])
            || empty($row['configuration']['parameters']['objects'])
        ) {
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
            $processors[1]['parameters']['tables'][] = [
                'table' => $tableName,
                'metadata' =>
                    [
                        [
                            'key' => OrchestrationImporter::SCAFFOLD_TABLE_TAG,
                            'value' => sprintf(
                                '%s.%s.%s',
                                OrchestrationImporter::SCAFFOLD_VALUE_PREFIX,
                                $this->operationImport->getOperationId(),
                                $tableName
                            ),
                        ],
                    ],
            ];
        }

        if (empty($row['processors'])) {
            $row['processors'] = [];
        }
        if (empty($row['processors']['after'])) {
            $row['processors']['after'] = [];
        }

        $afterProcessors = array_merge_recursive($row['processors']['after'], $processors);
        $row['processors'][self::USER_ACTION_KEY_PREFIX . '.after'] = $afterProcessors;
        unset($row['processors']['after']);

        return $row;
    }
}
