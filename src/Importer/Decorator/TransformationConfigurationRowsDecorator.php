<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer\Decorator;

use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\OrchestrationImporter;

class TransformationConfigurationRowsDecorator extends AbstractDecorator
{

    public function getDecoratedProjectImport(
        OperationImport $operationImport
    ): OperationImport {
        $decoratedRows = [];
        foreach ($operationImport->getConfigurationRows() as $row) {
            $decoratedRows[] = $this->getDecoratedTransformationConfigurationRow($row, $operationImport);
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

    private function getDecoratedTransformationConfigurationRow(
        array $row,
        OperationImport $operationImport
    ): array {
        $row = $this->decorateOutputMapping($row, $operationImport);
        $row = $this->addInputSourceSearch($row, $operationImport);

        return $row;
    }

    private function decorateOutputMapping(
        array $row,
        OperationImport $operationImport
    ): array {
        foreach ($row['configuration']['output'] as &$output) {
            if (!empty($output['destination'])) {
                if (!isset($output['metadata'])) {
                    $output['metadata'] = [];
                }

                $originalDestination = $output['destination'];
                // this will reorder destination to bottom
                unset($output['destination']);
                $output['destination'] = $this->convertTableName(
                    $operationImport,
                    $originalDestination
                );
                $output[self::USER_ACTION_KEY_PREFIX . 'original_destination'] = $originalDestination;
                $metadataValue = $this->convertTableNameToMetadataValue(
                    $operationImport,
                    $originalDestination
                );
                $output['metadata'][] = [
                    'key' => OrchestrationImporter::SCAFFOLD_TABLE_TAG,
                    'value' => $metadataValue,
                ];
                $output['metadata'][] = [
                    'key' => DecoratorInterface::SCAFFOLD_ID_TAG,
                    'value' => $operationImport->getScaffoldId(),
                ];
            }
        }

        return $row;
    }

    private function addInputSourceSearch(
        array $row,
        OperationImport $operationImport
    ): array {
        foreach ($row['configuration']['input'] as &$input) {
            if (!empty($input['source']) && empty($input['source_search'])) {
                $input['source_search'] = [
                    // value is annotated with "USER_ACTION_KEY_PREFIX" to notify user that this needs to be checked
                    'key' => OrchestrationImporter::SCAFFOLD_TABLE_TAG,
                    self::USER_ACTION_KEY_PREFIX . '.value' =>
                        $this->convertTableNameToMetadataValue($operationImport, $input['source']),
                ];

                // add decorated_source as source can be configuration row of same transformation
                // and source_search can't be used in this case
                $input[self::USER_ACTION_KEY_PREFIX . '.source'] = $this->convertTableName(
                    $operationImport,
                    $input['source']
                );
                // remove source, leave original source with prefix to user for check
                $input[self::USER_ACTION_KEY_PREFIX . '.original_source'] = $input['source'];
                unset($input['source']);
            }
        }

        return $row;
    }

    public function supports(OperationImport $operationImport): bool
    {
        if (count($operationImport->getConfigurationRows()) === 0) {
            return false;
        }

        return $operationImport->getComponentId() === 'transformation';
    }
}
