<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

final class TableNameConverterHelper
{
    public static function convertTableNameForMetadata(
        OperationImport $operationImport,
        string $tableName,
        string $component = 'internal'
    ): string {
        $tableName = TableNameConverterHelper::convertStagedTableName($operationImport, $tableName, true);
        $tableName = TableNameConverterHelper::convertToCamelCase($tableName, true);
        return sprintf(
            '%s.%s.%s',
            $operationImport->getScaffoldId(),
            $component,
            $tableName
        );
    }

    public static function convertStagedTableName(
        OperationImport $operationImport,
        string $destinationTableName,
        bool $removeBucket = false,
        bool $removeBucketPrefix = true
    ): string {
        $pattern = '/'
            . '([^\.]+)' // stage $1
            . '\.c-' // .c-
            . '([^\.]+)' // bucket name $2
            . '\.' // dot
            . '(.*)'  // table name $3
            . '/';
        $isMatched = preg_match($pattern, $destinationTableName, $matches);
        if (false === $isMatched || 4 !== count($matches)) {
            return $destinationTableName;
        }

        $stage = $matches[1];

        if (true === $removeBucketPrefix) {
            $replacement = sprintf('%s.%s.$3', $stage, $operationImport->getScaffoldId());
        } else {
            $replacement = sprintf('%s.c-%s.$3', $stage, $operationImport->getScaffoldId());
        }

        if (true === $removeBucket) {
            $replacement = sprintf('%s.$3', $stage);
        }

        return preg_replace($pattern, $replacement, $destinationTableName);
    }

    public static function convertToCamelCase(
        string $string,
        bool $includeUnderscore = false
    ): string {
        $stopWords = ['-', '.', ' '];
        if (true === $includeUnderscore) {
            $stopWords[] = '_';
        }

        foreach ($stopWords as $delimiter) {
            $string = ucwords($string, $delimiter);
        }
        return lcfirst(str_replace($stopWords, '', $string));
    }
}
