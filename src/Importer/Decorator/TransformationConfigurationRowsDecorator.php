<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer\Decorator;

use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\OrchestrationImporter;

class TransformationConfigurationRowsDecorator implements DecoratorInterface
{
    public function getDecoratedProjectImport(
        OperationImport $operationImport
    ): OperationImport {
        $decoratedRows = [];
        foreach ($operationImport->getConfigurationRows() as $row) {
            $decoratedRows[] = $this->getDecoratedTransformationConfigurationRow($row, $operationImport);
        }

        return new OperationImport(
            $operationImport->getOperationId(),
            $operationImport->getComponentId(),
            $operationImport->getPayload(),
            $operationImport->getTask(),
            $decoratedRows
        );
    }

    private function getDecoratedTransformationConfigurationRow(
        array $row,
        OperationImport $operationImport
    ): array {
        $row = $this->addOutputMetadata($row, $operationImport);
        $row = $this->addInputSourceSearch($row);

        return $row;
    }

    private function addOutputMetadata(
        array $row,
        OperationImport $operationImport
    ): array {
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
                        $operationImport->getOperationId(),
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

    public function supports(OperationImport $operationImport): bool
    {
        if (0 === count($operationImport->getConfigurationRows())) {
            return false;
        }

        return $operationImport->getComponentId() === 'transformation';
    }
}
