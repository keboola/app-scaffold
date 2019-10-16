<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

final class TableNameConverter
{
    private const EXPECTED_MATCH_COUNT = 4;
    private const STAGED_TABLE_MATCH_PATTERN = '/'
    . '([^\.]+)' // stage $1
    . '\.c-' // .c-
    . '([^\.]+)' // bucket name $2
    . '\.' // dot
    . '(.*)'  // table name $3
    . '/';

    public function convertTableName(
        OperationImport $operationImport,
        string $tableName
    ): string {
        $tableParts = $this->matchTableName($tableName);
        if (null === $tableParts) {
            // when not matching bucket table patern convert only to camel case
            return CamelCaseConverterHelper::convertToCamelCase(
                $tableName
            );
        }
        [, $stage, $bucketName, $tableName] = $tableParts;

        $scaffoldId = CamelCaseConverterHelper::convertToCamelCase(
            $operationImport->getScaffoldId()
        );
        $tableName = CamelCaseConverterHelper::convertToCamelCase(
            $bucketName . '.' . $tableName
        );

        return sprintf('%s.c-%s.%s', $stage, $scaffoldId, $tableName);
    }

    private function matchTableName(string $tableName): ?array
    {
        $isMatched = preg_match(self::STAGED_TABLE_MATCH_PATTERN, $tableName, $matches);
        if (false === $isMatched || self::EXPECTED_MATCH_COUNT !== count($matches)) {
            return null;
        };
        return $matches;
    }

    public function convertTableNameToMetadataValue(
        OperationImport $operationImport,
        string $tableName
    ): string {
        $tableParts = $this->matchTableName($tableName);
        if (null === $tableParts) {
            // in this case table si simple string and not in stage.c-bucketName.tableName format
            $tableName = CamelCaseConverterHelper::convertToCamelCase(
                $tableName
            );
            return sprintf(
                '%s.internal.%s',
                $operationImport->getScaffoldId(),
                $tableName
            );
        }
        [/** ignore $0 match */, $stage, $bucketName, $tableName] = $tableParts;

        $tagName = CamelCaseConverterHelper::convertToCamelCase(sprintf(
            '%s-%s-%s', // "-" will be removed it's only for camel case conversion
            $stage,
            $bucketName,
            $tableName
        ));

        // scaffoldId is not converted to camel case
        return sprintf('%s.internal.%s', $operationImport->getScaffoldId(), $tagName);
    }
}
