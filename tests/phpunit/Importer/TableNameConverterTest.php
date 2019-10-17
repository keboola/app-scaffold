<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer;

use Keboola\Orchestrator\OrchestrationTask;
use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\TableNameConverter;
use PHPUnit\Framework\TestCase;

class TableNameConverterTest extends TestCase
{
    public const CONVERT_TABLE_NAME_TO_METADATA_VALUES = [
        [
            'expected' => 'scaffoldId.internal.outCrmCompany',
            'input' => 'out.c-crm.company',
        ],
        [ // not matching patern
            'expected' => 'scaffoldId.internal.somethingElse',
            'input' => 'something-else',
        ],
    ];
    public const CONVERT_TABLE_NAME_VALUES = [
        [
            'expected' => 'out.c-scaffoldId.crmCompany',
            'input' => 'out.c-crm.company',
        ],
        [ // not matching patern only camel case
            'expected' => 'somethingElse',
            'input' => 'something-else',
        ],
    ];

    /**
     * @var TableNameConverter
     */
    private $converterInstance;

    public function convertTableNameProvider(): array
    {
        return self::CONVERT_TABLE_NAME_VALUES;
    }

    public function convertTableNameToMetadataValueProvider(): array
    {
        return self::CONVERT_TABLE_NAME_TO_METADATA_VALUES;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->converterInstance = new TableNameConverter;
    }

    /**
     * @dataProvider convertTableNameProvider
     */
    public function testConvertTableName(string $expected, string $input): void
    {
        $import = $this->getOperationImport();
        self::assertEquals(
            $expected,
            $this->converterInstance->convertTableName($import, $input)
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

    /**
     * @dataProvider convertTableNameToMetadataValueProvider
     */
    public function testConvertTableNameToMetadataValue(
        string $expected,
        string $input
    ): void {
        $import = $this->getOperationImport();
        self::assertEquals(
            $expected,
            $this->converterInstance->convertTableNameToMetadataValue($import, $input)
        );
    }
}
