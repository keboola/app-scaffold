<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

final class TableNameConverterHelper
{
    public static function convertDestinationTableName(
        OperationImport $operationImport,
        string $destinationTableName
    ): string {
        if (strpos($destinationTableName, 'out.c-') !== 0) {
            return $destinationTableName;
        }

        $pattern = '/out\.c-([^\.]+)\.(.*)/';
        $replacement = sprintf('out.c-%s.$2', $operationImport->getScaffoldId());

        return preg_replace($pattern, $replacement, $destinationTableName);
    }

    public static function convertTableNameForMetadata(
        OperationImport $operationImport,
        string $tableName
    ): string {
        return sprintf(
            '%s.internal.%s',
            $operationImport->getScaffoldId(),
            str_replace('.', '_', $tableName)
        );
    }

    public static function convertToCamelCase(string $string): string
    {
        foreach (['-', '.', ' '] as $delimiter) {
            $string = ucwords($string, $delimiter);
        }
        return str_replace(['-', '.', ' '], '', $string);
    }
}
