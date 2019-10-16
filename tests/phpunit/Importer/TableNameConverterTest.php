<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer;

use Keboola\Orchestrator\OrchestrationTask;
use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\TableNameConverter;
use PHPUnit\Framework\TestCase;

class TableNameConverterTest extends TestCase
{
    /**
     * @var TableNameConverter
     */
    private $converterInstance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converterInstance = new TableNameConverter;
    }

    public function testConvertTableName(): void
    {
        $import = $this->getOperationImport();
        self::assertEquals(
            'out.c-scaffoldId.crmCompany',
            $this->converterInstance->convertTableName($import, 'out.c-crm.company')
        );

        // not matching patern only camel case
        self::assertEquals(
            'somethingElse',
            $this->converterInstance->convertTableName($import, 'something-else')
        );
    }

    public function testConvertTableNameToMetadataValue(): void
    {
        $import = $this->getOperationImport();
        self::assertEquals(
            'scaffoldId.internal.outCrmCompany',
            $this->converterInstance->convertTableNameToMetadataValue($import, 'out.c-crm.company')
        );

        // not matching patern
        self::assertEquals(
            'scaffoldId.internal.somethingElse',
            $this->converterInstance->convertTableNameToMetadataValue($import, 'something-else')
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
}
