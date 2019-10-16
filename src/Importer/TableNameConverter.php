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
            return $tableName;
        }
        [, $stage, $bucketName, $tableName] = $tableParts;

        return CamelCaseConverterHelper::convertToCamelCase(sprintf(
            '%s.%s.%s-%s',
            $stage,
            $operationImport->getScaffoldId(),
            $bucketName,
            $tableName
        ));
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
            return CamelCaseConverterHelper::convertToCamelCase(sprintf(
                '%s.internal.%s',
                $operationImport->getScaffoldId(),
                $tableName
            ));
        }
        [/** ignore $0 match */, $stage, $bucketName, $tableName] = $tableParts;

        $tableName = CamelCaseConverterHelper::convertToCamelCase(
            $tableName,
            CamelCaseConverterHelper::STOP_WORDS_FULL
        );

        // scaffoldId is not converted to camel case
        return $operationImport->getScaffoldId()
            . CamelCaseConverterHelper::convertToCamelCase(sprintf(
                '.internal.%s-%s-%s',
                $stage,
                $bucketName,
                $tableName
            ));
    }
}
