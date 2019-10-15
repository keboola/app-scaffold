<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer;

use Keboola\Orchestrator\OrchestrationTask;
use Keboola\ScaffoldApp\Importer\TableNameConverterHelper;
use Keboola\ScaffoldApp\Importer\OperationImport;
use PHPUnit\Framework\TestCase;

class TableNameConverterHelperTest extends TestCase
{
    public function testConvertDestinationTableName(): void
    {
        $import = $this->getOperationImport();
        $converted = TableNameConverterHelper::convertDestinationTableName($import, 'out.c-customName.tableName');
        self::assertEquals(
            'out.c-scaffoldId.tableName',
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

    public function testConvertDestinationTableNameNotDestinationTableName(): void
    {
        $import = $this->getOperationImport();
        $converted = TableNameConverterHelper::convertDestinationTableName($import, 'notOut.c-scaffoldId.tableName');
        self::assertEquals(
            'notOut.c-scaffoldId.tableName',
            $converted
        );
    }

    public function testConvertTableNameForMetadata(): void
    {
        $import = $this->getOperationImport();
        $converted = TableNameConverterHelper::convertTableNameForMetadata($import, 'out.c-crm.company');
        self::assertEquals(
            'scaffoldId.internal.out_c-crm_company',
            $converted
        );
    }

    public function testConvertToCamelCase(): void
    {
        $converted = TableNameConverterHelper::convertToCamelCase('keboola.ex-snowflake sufix');
        self::assertEquals('KeboolaExSnowflakeSufix', $converted);
    }
}
