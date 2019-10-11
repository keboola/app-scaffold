<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer;

use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\OperationImportFactory;

class OperationImportDecoratorTest extends ImporterBaseTestCase
{
    public function testGetDecoratedOperationImportNoDecoration(): void
    {
        $task = $this->getExampleOrchstrationTask();

        $configuration = [
            'name' => 'configurationName',
            'configuration' => [
                'parameters' => [],
            ],
            'processors' => [
                'after' => [],
            ],
            'rows' => [
                [
                    'configuration' => [
                        'parameters' => [],
                    ],
                    'processors' => [],
                ],
            ],
        ];

        $operationImport = OperationImportFactory::createOperationImport(
            $configuration,
            $task
        );

        self::assertInstanceOf(OperationImport::class, $operationImport);
        self::assertStringStartsWith('keboolaComponent_', $operationImport->getOperationId());
        self::assertEquals('keboola.component', $operationImport->getComponentId());
        self::assertSame($configuration['rows'], $operationImport->getConfigurationRows());
        self::assertStringStartsWith(
            'keboolaComponent_',
            $operationImport->getOrchestrationTaskJsonArray()['operationReferenceId']
        );
    }

    public function testGetDecoratedOperationImportParametersObjects(): void
    {
        $task = $this->getExampleOrchstrationTask();

        $configuration = [
            'name' => 'configurationName',
            'configuration' => [
                'parameters' => [],
            ],
            'processors' => [
                'after' => [],
            ],
            'rows' => [
                [
                    'configuration' => [
                        'parameters' => [
                            'objects' => [
                                [
                                    'name' => 'sampleTableName',
                                ],
                                [
                                    'somethingDifferent' => 'sampleValue',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $operationImport = OperationImportFactory::createOperationImport(
            $configuration,
            $task
        );

        self::assertStringStartsWith('keboolaComponent_', $operationImport->getOperationId());
        self::assertEquals('keboola.component', $operationImport->getComponentId());
        self::assertStringStartsWith(
            'keboolaComponent_',
            $operationImport->getOrchestrationTaskJsonArray()['operationReferenceId']
        );

        $expectedRows = [
            [
                'configuration' => [
                    'parameters' => [
                        'objects' => [
                            [
                                'name' => 'sampleTableName',
                            ],
                            [
                                'somethingDifferent' => 'sampleValue',
                            ],
                        ],
                    ],
                ],
                'processors' => [
                    '__SCAFFOLD_CHECK__.after' => [
                        [
                            'definition' =>
                                [
                                    'component' => 'keboola.processor-create-manifest',
                                ],
                            'parameters' =>
                                [
                                    'delimiter' => ',',
                                    'enclosure' => '"',
                                    'incremental' => false,
                                    'primary_key' =>
                                        [
                                        ],
                                    'columns_from' => 'header',
                                ],
                        ],
                        [
                            'definition' =>
                                [
                                    'component' => 'keboola.processor-add-metadata',
                                ],
                            'parameters' =>
                                [
                                    'tables' =>
                                        [
                                            [
                                                'table' => 'sampleTableName',
                                                'metadata' => [
                                                    [
                                                        'key' => 'bdm.scaffold.table.tag',
                                                        'value' => 'bdm.scaffold.'
                                                            . $operationImport->getOperationId()
                                                            . '.sampleTableName',
                                                    ],
                                                ],
                                            ],
                                        ],
                                ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame($expectedRows, $operationImport->getConfigurationRows());
    }

    public function testGetDecoratedOperationImportTransformationRows(): void
    {
        $task = $this->getExampleOrchstrationTask();
        $task->setComponent('transformation');

        $configuration = [
            'name' => 'configurationName',
            'configuration' => [
                'parameters' => [],
            ],
            'processors' => [
                'after' => [],
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
            ],
        ];

        $operationImport = OperationImportFactory::createOperationImport(
            $configuration,
            $task
        );

        self::assertInstanceOf(OperationImport::class, $operationImport);
        self::assertStringStartsWith('transformation_', $operationImport->getOperationId());
        self::assertEquals('transformation', $operationImport->getComponentId());
        self::assertStringStartsWith(
            'transformation_',
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
                                    'bdm.scaffold.__change_operation_id__.in.c-salesforce.account',
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
                                    'value' => 'bdm.scaffold.' . $operationImport->getOperationId() . '.account',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame($expectedRows, $operationImport->getConfigurationRows());
    }
}
