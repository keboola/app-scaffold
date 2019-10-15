<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer\Decorator\ComponentSpecific;

use Keboola\ScaffoldApp\Importer\Decorator\ComponentSpecific\WrDbSnowflakeDecorator;
use Keboola\ScaffoldApp\Importer\OperationImportFactory;
use Keboola\ScaffoldApp\Tests\Importer\ImporterBaseTestCase;

class WrDbSnowflakeDecoratorTest extends ImporterBaseTestCase
{
    public function testGetDecoratedProjectImport(): void
    {
        $task = $this->getExampleOrchstrationTask();
        $task->setComponent('keboola.wr-db-snowflake');

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

        self::assertEquals('keboolaWrDbSnowflake', $operationImport->getOperationId());
        self::assertEquals('keboola.wr-db-snowflake', $operationImport->getComponentId());
        self::assertEquals(
            'keboolaWrDbSnowflake',
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
                                    '__SCAFFOLD_CHECK__.value' => 'scaffoldId.internal.out_c-ProjectName_tableName',
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
        $task->setComponent('keboola.wr-db-snowflake');

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

        $decorator = new WrDbSnowflakeDecorator();
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

    public function testSupportsOtherComponent(): void
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

        $decorator = new WrDbSnowflakeDecorator();

        self::assertFalse($decorator->supports($operationImport));
    }

    public function testSupportsValid(): void
    {
        $task = $this->getExampleOrchstrationTask();
        $task->setComponent('keboola.wr-db-snowflake');

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

        $decorator = new WrDbSnowflakeDecorator();

        self::assertTrue($decorator->supports($operationImport));
    }
}
