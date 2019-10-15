<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer\Decorator;

use Keboola\ScaffoldApp\Importer\Decorator\TransformationConfigurationRowsDecorator;
use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\OperationImportFactory;
use Keboola\ScaffoldApp\Tests\Importer\ImporterBaseTestCase;

class TransformationConfigurationRowsDecoratorTest extends ImporterBaseTestCase
{
    public function testGetDecoratorProjectImport(): void
    {
        $task = $this->getExampleOrchstrationTask();
        $task->setComponent('transformation');

        $configuration = [
            'name' => 'configurationName',
            'configuration' => [
                'parameters' => [],
                'processors' => [
                    'after' => [],
                ],
            ],
            'rows' => [
                [
                    'configuration' => [
                        'input' => [
                            [
                                'destination' => 'account',
                                'datatypes' => [],
                                'whereColumn' => '',
                                'whereValues' => [],
                                'whereOperator' => 'eq',
                                'columns' => [],
                                'loadType' => 'clone',
                                'source' => 'in.c-salesforce.account',
                            ],
                        ],
                        'output' => [
                            [
                                'destination' => 'account',
                                'datatypes' => [],
                                'whereColumn' => '',
                                'whereValues' => [],
                                'whereOperator' => 'eq',
                                'columns' => [],
                                'loadType' => 'clone',
                            ],
                        ],
                    ],
                ],
                [
                    'configuration' => [
                        'input' => [
                            [
                                'destination' => 'account2',
                                'datatypes' => [],
                                'whereColumn' => '',
                                'whereValues' => [],
                                'whereOperator' => 'eq',
                                'columns' => [],
                                'loadType' => 'clone',
                                'source_search' => [
                                    'key' => 'key1',
                                    'value' => 'val1',
                                ],
                            ],
                        ],
                        'output' => [
                            [
                                'destination' => 'account2',
                                'datatypes' => [],
                                'whereColumn' => '',
                                'whereValues' => [],
                                'whereOperator' => 'eq',
                                'columns' => [],
                                'loadType' => 'clone',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $operationImport = OperationImportFactory::createOperationImport(
            $configuration,
            $task,
            'scaffoldId'
        );

        self::assertInstanceOf(OperationImport::class, $operationImport);
        self::assertEquals('transformationConfigurationName', $operationImport->getOperationId());
        self::assertEquals('transformation', $operationImport->getComponentId());
        self::assertEquals(
            'transformationConfigurationName',
            $operationImport->getOrchestrationTaskJsonArray()['operationReferenceId']
        );

        $expectedRows = [
            [
                'configuration' => [
                    'input' => [
                        [
                            'destination' => 'account',
                            'datatypes' => [],
                            'whereColumn' => '',
                            'whereValues' => [],
                            'whereOperator' => 'eq',
                            'columns' => [],
                            'loadType' => 'clone',
                            'source_search' => [
                                'key' => 'bdm.scaffold.table.tag',
                                '__SCAFFOLD_CHECK__.value' =>
                                    'scaffoldId.internal.in_c-salesforce_account',
                            ],
                            '__SCAFFOLD_CHECK__.source' => 'in.c-salesforce.account',
                        ],
                    ],
                    'output' => [
                        [
                            'destination' => 'account',
                            'datatypes' => [],
                            'whereColumn' => '',
                            'whereValues' => [],
                            'whereOperator' => 'eq',
                            'columns' => [],
                            'loadType' => 'clone',
                            'metadata' => [
                                [
                                    'key' => 'bdm.scaffold.table.tag',
                                    'value' => 'scaffoldId.internal.account',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'configuration' => [
                    'input' => [
                        [
                            'destination' => 'account2',
                            'datatypes' => [],
                            'whereColumn' => '',
                            'whereValues' => [],
                            'whereOperator' => 'eq',
                            'columns' => [],
                            'loadType' => 'clone',
                            'source_search' => [
                                'key' => 'key1',
                                'value' => 'val1',
                            ],
                        ],
                    ],
                    'output' => [
                        [
                            'destination' => 'account2',
                            'datatypes' => [],
                            'whereColumn' => '',
                            'whereValues' => [],
                            'whereOperator' => 'eq',
                            'columns' => [],
                            'loadType' => 'clone',
                            'metadata' => [
                                [
                                    'key' => 'bdm.scaffold.table.tag',
                                    'value' => 'scaffoldId.internal.account2',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame($expectedRows, $operationImport->getConfigurationRows());
    }

    public function testSupportsEmptyRows(): void
    {
        $task = $this->getExampleOrchstrationTask();

        $operationImport = OperationImportFactory::createOperationImport(
            [
                'name' => '',
                'configuration' => '',
                'rows' => [],
            ],
            $task,
            'scaffoldId'
        );

        $decorator = new TransformationConfigurationRowsDecorator();

        self::assertFalse($decorator->supports($operationImport));
    }

    public function testSupportsNotTransformation(): void
    {
        $task = $this->getExampleOrchstrationTask();

        $operationImport = OperationImportFactory::createOperationImport(
            [
                'name' => '',
                'configuration' => '',
                'rows' => [
                    [
                        'configuration' => [
                            'output' => [],
                            'input' => [],
                        ],
                    ],
                ],
            ],
            $task,
            'scaffoldId'
        );

        $decorator = new TransformationConfigurationRowsDecorator();

        self::assertFalse($decorator->supports($operationImport));
    }

    public function testSupportsValid(): void
    {
        $task = $this->getExampleOrchstrationTask();
        $task->setComponent('transformation');

        $configuration = [
            'name' => '',
            'configuration' => '',
            'rows' => [
                [
                    'configuration' => [
                        'output' => [],
                        'input' => [],
                    ],
                ],
            ],
        ];

        $operationImport = OperationImportFactory::createOperationImport(
            $configuration,
            $task,
            'scaffoldId'
        );

        $decorator = new TransformationConfigurationRowsDecorator();

        self::assertTrue($decorator->supports($operationImport));
    }
}
