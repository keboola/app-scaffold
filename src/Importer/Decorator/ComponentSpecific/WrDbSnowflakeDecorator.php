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
class WrDbSnowflakeDecorator implements DecoratorInterface
{
    private const SUPPORTED_COMPONENTS = ['keboola.wr-db-snowflake'];

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
                $table['source_search'] = [
                    // value is annotated with "USER_ACTION_KEY_PREFIX" to notify user that this needs to be checked
                    'key' => OrchestrationImporter::SCAFFOLD_TABLE_TAG,
                    self::USER_ACTION_KEY_PREFIX . '.value' =>
                        TableNameConverterHelper::convertTableNameForMetadata($operationImport, $table['source']),
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
        if (!in_array($operationImport->getComponentId(), self::SUPPORTED_COMPONENTS)) {
            return false;
        }

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
