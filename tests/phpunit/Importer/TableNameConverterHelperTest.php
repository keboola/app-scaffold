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
        $import = $this->getOperationImport();
        $converted = TableNameConverterHelper::convertStagedTableName($import, 'out.c-bucketName.tableName');
        self::assertEquals(
            'out.scaffoldId.tableName',
            $converted
        );
        // don't remove prefix
        $converted = TableNameConverterHelper::convertStagedTableName($import, 'out.c-bucketName.tableName', false);
        self::assertEquals(
            'out.c-scaffoldId.tableName',
            $converted
        );
        // different stage
        $converted = TableNameConverterHelper::convertStagedTableName($import, 'stage.c-bucketName.tableName', false);
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
            'scaffoldId.internal.outScaffoldIdCompany',
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
