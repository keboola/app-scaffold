<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer\Decorator;

use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\OrchestrationImporter;

/**
 * # Use case:
 *
 * ## Configuration rows specification:
 *
 * Component has path "configuration.storage.input.tables[].source"
 *
 * Each object has property "source"
 *
 * ## Decorator function:
 *
 * For each table is added `source_search` key
 * and original source is kept under `__SCAFFOLD_CHECK__.original_source`
 *
 */
class StorageInputTablesDecorator extends AbstractDecorator
{

    public function getDecoratedProjectImport(
        OperationImport $operationImport
    ): OperationImport {
        return new OperationImport(
            $operationImport->getScaffoldId(),
            $operationImport->getOperationId(),
            $operationImport->getComponentId(),
            $this->getDecoratedPayload($operationImport),
            $operationImport->getTask(),
            $operationImport->getConfigurationRows()
        );
    }

    private function getDecoratedPayload(
        OperationImport $operationImport
    ): array {
        $payload = $operationImport->getPayload();
        foreach ($payload['configuration']['storage']['input']['tables'] as &$table) {
            if (!empty($table['source']) && empty($table['source_search'])) {
                $convertedMetadataValue =
                    $this->convertTableNameToMetadataValue(
                        $operationImport,
                        $table['source']
                    );

                $table['source_search'] = [
                    // value is annotated with "USER_ACTION_KEY_PREFIX" to notify user that this needs to be checked
                    'key' => OrchestrationImporter::SCAFFOLD_TABLE_TAG,
                    self::USER_ACTION_KEY_PREFIX . '.value' => $convertedMetadataValue,
                ];

                // remove source, leave original source with prefix to user for check
                $table[self::USER_ACTION_KEY_PREFIX . '.original_source'] = $table['source'];
                unset($table['source']);
            }
        }

        return $payload;
    }

    public function supports(OperationImport $operationImport): bool
    {
        $payload = $operationImport->getPayload();
        if (empty($payload['configuration']['storage'])
            || empty($payload['configuration']['storage']['input'])
            || empty($payload['configuration']['storage']['input']['tables'])
        ) {
            return false;
        }

        return true;
    }
}
