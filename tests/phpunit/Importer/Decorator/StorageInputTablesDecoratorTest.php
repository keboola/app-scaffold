<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer\Decorator;

use Keboola\ScaffoldApp\Importer\Decorator\StorageInputTablesDecorator;
use Keboola\ScaffoldApp\Importer\OperationImportFactory;
use Keboola\ScaffoldApp\Tests\Importer\ImporterBaseTestCase;

class StorageInputTablesDecoratorTest extends ImporterBaseTestCase
{
    public function testGetDecoratedProjectImport(): void
    {
        $task = $this->getExampleOrchstrationTask();

        $configuration = [
            'name' => '',
            'configuration' => [
                'storage' => [
                    'input' => [
                        'tables' => [
                            [
                                'otherKey' => 'keyValue',
                                'source' => 'exampleSource',
                            ],
                            [
                                'otherKey' => 'keyValue',
                                'source' => 'out.c-ProjectName.tableName',
                            ],
                        ],
                    ],
                ],
            ],
            'rows' => [
            ],
        ];

        $operationImport = OperationImportFactory::createOperationImport(
            $configuration,
            $task,
            'scaffoldId'
        );

        self::assertEquals('keboolaComponent', $operationImport->getOperationId());
        self::assertEquals('keboola.component', $operationImport->getComponentId());
        self::assertEquals(
            'keboolaComponent',
            $operationImport->getOrchestrationTaskJsonArray()['operationReferenceId']
        );

        $expectedPayload = [
            'name' => '',
            'configuration' => [
                'storage' => [
                    'input' => [
                        'tables' => [
                            [
                                'otherKey' => 'keyValue',
                                'source_search' => [
                                    'key' => 'bdm.scaffold.table.tag',
                                    '__SCAFFOLD_CHECK__.value' => 'scaffoldId.internal.exampleSource',
                                ],
                                '__SCAFFOLD_CHECK__.original_source' => 'exampleSource',
                            ],
                            [
                                'otherKey' => 'keyValue',
                                'source_search' => [
                                    'key' => 'bdm.scaffold.table.tag',
                                    '__SCAFFOLD_CHECK__.value' => 'scaffoldId.internal.outScaffoldIdTableName',
                                ],
                                '__SCAFFOLD_CHECK__.original_source' => 'out.c-ProjectName.tableName',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame($expectedPayload, $operationImport->getPayload());
    }

    public function testSupportsInvlidInput(): void
    {
        $task = $this->getExampleOrchstrationTask();

        $configuration = [
            'name' => '',
            'configuration' => [
                'storage' => [
                    'input' => [
                        'tables' => [
                            // empty tables
                        ],
                    ],
                ],
            ],
            'rows' => [
            ],
        ];

        $operationImport = OperationImportFactory::createOperationImport(
            $configuration,
            $task,
            'scaffoldId'
        );

        $decorator = new StorageInputTablesDecorator();
        self::assertFalse($decorator->supports($operationImport));

        $configuration = [
            'name' => '',
            'configuration' => [
                'storage' => [
                    'input' => [
                        // missing tables
                    ],
                ],
            ],
            'rows' => [
            ],
        ];

        $operationImport = OperationImportFactory::createOperationImport(
            $configuration,
            $task,
            'scaffoldId'
        );

        self::assertFalse($decorator->supports($operationImport));

        $configuration = [
            'name' => '',
            'configuration' => [
                'storage' => [
                    // missing input
                ],
            ],
            'rows' => [
            ],
        ];

        $operationImport = OperationImportFactory::createOperationImport(
            $configuration,
            $task,
            'scaffoldId'
        );

        self::assertFalse($decorator->supports($operationImport));

        $configuration = [
            'name' => '',
            'configuration' => [
                // missing storage
            ],
            'rows' => [
            ],
        ];

        $operationImport = OperationImportFactory::createOperationImport(
            $configuration,
            $task,
            'scaffoldId'
        );

        self::assertFalse($decorator->supports($operationImport));
    }

    public function testSupportsValid(): void
    {
        $task = $this->getExampleOrchstrationTask();

        $configuration = [
            'name' => '',
            'configuration' => [
                'storage' => [
                    'input' => [
                        'tables' => [
                            [
                                'otherKey' => 'keyValue',
                                'source' => 'exampleSource',
                            ],
                        ],
                    ],
                ],
            ],
            'rows' => [
            ],
        ];

        $operationImport = OperationImportFactory::createOperationImport(
            $configuration,
            $task,
            'scaffoldId'
        );

        $decorator = new StorageInputTablesDecorator();

        self::assertTrue($decorator->supports($operationImport));
    }
}
