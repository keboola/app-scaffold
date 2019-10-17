<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer\Decorator;

use Keboola\Orchestrator\OrchestrationTask;
use Keboola\ScaffoldApp\Importer\Decorator\AbstractDecorator;
use Keboola\ScaffoldApp\Importer\Decorator\DecoratorInterface;
use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Tests\Importer\TableNameConverterTest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\NullOutput;

class AbstractDecoratorTest extends TestCase
{
    public function convertTableNameProvider(): array
    {
        return TableNameConverterTest::CONVERT_TABLE_NAME_VALUES;
    }

    public function convertTableNameToMetadataValueProvider(): array
    {
        return TableNameConverterTest::CONVERT_TABLE_NAME_TO_METADATA_VALUES;
    }

    /**
     * @dataProvider convertTableNameProvider
     */
    public function testConvertTableName(string $expected, string $input): void
    {
        $import = $this->getOperationImport();
        $abstractDecorator = $this->getInstance();
        self::assertEquals(
            $expected,
            $abstractDecorator->convertTableName($import, $input)
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

    public function getInstance(): AbstractDecorator
    {
        return new class(new NullOutput) extends AbstractDecorator
        {
            public function getDecoratedProjectImport(
                OperationImport $operationImport
            ): OperationImport {
                return $operationImport;
            }

            public function supports(OperationImport $operationImport): bool
            {
                return true;
            }
        };
    }

    /**
     * @dataProvider convertTableNameToMetadataValueProvider
     */
    public function testConvertTableNameToMetadataValue(
        string $expected,
        string $input
    ): void {
        $import = $this->getOperationImport();
        $abstractDecorator = $this->getInstance();
        self::assertEquals(
            $expected,
            $abstractDecorator->convertTableNameToMetadataValue($import, $input)
        );
    }

    public function testInstance(): void
    {
        self::assertInstanceOf(DecoratorInterface::class, $this->getInstance());
    }
}
