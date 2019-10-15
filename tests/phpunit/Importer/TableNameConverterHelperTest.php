<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer;

use Keboola\Orchestrator\OrchestrationTask;
use Keboola\ScaffoldApp\Importer\TableNameConverterHelper;
use Keboola\ScaffoldApp\Importer\OperationImport;
use PHPUnit\Framework\TestCase;

class TableNameConverterHelperTest extends TestCase
{
    public function testConvertStagedTableName(): void
    {
        $tableName = 'stage.c-bucketName.tableName';

        // don't remove Bucket default parameters
        $import = $this->getOperationImport();
        $converted = TableNameConverterHelper::convertStagedTableName($import, $tableName);
        self::assertEquals(
            'stage.scaffoldId.tableName',
            $converted
        );

        // don't remove Bucket default parameters listed
        $import = $this->getOperationImport();
        $converted = TableNameConverterHelper::convertStagedTableName($import, $tableName, false, true);
        self::assertEquals(
            'stage.scaffoldId.tableName',
            $converted
        );

        // remove Bucket
        $import = $this->getOperationImport();
        $converted = TableNameConverterHelper::convertStagedTableName($import, $tableName, true, true);
        self::assertEquals(
            'stage.tableName',
            $converted
        );

        // remove Bucket
        $import = $this->getOperationImport();
        $converted = TableNameConverterHelper::convertStagedTableName($import, $tableName, true, false);
        self::assertEquals(
            'stage.tableName',
            $converted
        );

        // don't remove prefix
        $converted = TableNameConverterHelper::convertStagedTableName($import, $tableName, false, false);
        self::assertEquals(
            'stage.c-scaffoldId.tableName',
            $converted
        );
    }

    private function getOperationImport(): OperationImport
    {
        $import = new OperationImport(
            'scaffoldId',
            'operationId',
            'componentId',
            [],
            new OrchestrationTask()
        );
        return $import;
    }

    public function testConvertTableNameForMetadata(): void
    {
        $import = $this->getOperationImport();
        $converted = TableNameConverterHelper::convertTableNameForMetadata($import, 'out.c-crm.company');
        self::assertEquals(
            'scaffoldId.internal.outCompany',
            $converted
        );

        $import = $this->getOperationImport();
        $converted = TableNameConverterHelper::convertTableNameForMetadata($import, 'out.c-crm.company', 'exSnowflake');
        self::assertEquals(
            'scaffoldId.exSnowflake.outCompany',
            $converted
        );
    }

    public function testConvertToCamelCase(): void
    {
        $converted = TableNameConverterHelper::convertToCamelCase('keboola.ex-snowflake sufix');
        self::assertEquals('keboolaExSnowflakeSufix', $converted);

        // include underscore
        $converted = TableNameConverterHelper::convertToCamelCase('keboola.ex-snowflake_sufix', false);
        self::assertEquals('keboolaExSnowflake_sufix', $converted);
        $converted = TableNameConverterHelper::convertToCamelCase('keboola.ex-snowflake_sufix', true);
        self::assertEquals('keboolaExSnowflakeSufix', $converted);
    }
}
