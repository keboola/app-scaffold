<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer\Decorator;

use Keboola\ScaffoldApp\Importer\Decorator\StorageInputTablesDecorator;
use Keboola\ScaffoldApp\Importer\OperationImportFactory;
use Keboola\ScaffoldApp\Tests\Importer\ImporterBaseTestCase;
use Symfony\Component\Console\Output\NullOutput;

class StorageInputTablesDecoratorTest extends ImporterBaseTestCase
{
    public function testGetDecoratedProjectImport(): void
    {
        $task = $this->getExampleOrchestrationTask();

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
                                'source' => 'out.c-BucketName.tableName',
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
            'scaffoldId',
            new NullOutput
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
                                    '__SCAFFOLD_CHECK__.value' => 'scaffoldId.internal.outBucketNameTableName',
                                ],
                                '__SCAFFOLD_CHECK__.original_source' => 'out.c-BucketName.tableName',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame($expectedPayload, $operationImport->getPayload());
    }

    public function testSupportsInvalidInput(): void
    {
        $task = $this->getExampleOrchestrationTask();

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
            'scaffoldId',
            new NullOutput
        );

        $decorator = new StorageInputTablesDecorator(new NullOutput);
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
            'scaffoldId',
            new NullOutput
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
            'scaffoldId',
            new NullOutput
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
            'scaffoldId',
            new NullOutput
        );

        self::assertFalse($decorator->supports($operationImport));
    }

    public function testSupportsValid(): void
    {
        $task = $this->getExampleOrchestrationTask();

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
            'scaffoldId',
            new NullOutput
        );

        $decorator = new StorageInputTablesDecorator(new NullOutput);

        self::assertTrue($decorator->supports($operationImport));
    }
}
