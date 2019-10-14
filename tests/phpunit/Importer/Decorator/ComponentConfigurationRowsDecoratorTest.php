<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer\Decorator;

use Keboola\ScaffoldApp\Importer\Decorator\ComponentConfigurationRowsDecorator;
use Keboola\ScaffoldApp\Importer\OperationImportFactory;
use Keboola\ScaffoldApp\Tests\Importer\ImporterBaseTestCase;

class ComponentConfigurationRowsDecoratorTest extends ImporterBaseTestCase
{
    public function testGetDecoratorProjectImport(): void
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
            $task,
            'scaffoldId'
        );

        self::assertEquals('keboolaComponentConfigurationName', $operationImport->getOperationId());
        self::assertEquals('keboola.component', $operationImport->getComponentId());
        self::assertEquals(
            'keboolaComponentConfigurationName',
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
                                                            'value' => 'scaffoldId.internal.sampleTableName',
                                                        ],
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

        $decorator = new ComponentConfigurationRowsDecorator();

        self::assertFalse($decorator->supports($operationImport));
    }

    public function testSupportsNoParametersObjects(): void
    {
        $task = $this->getExampleOrchstrationTask();

        $configuration = [
            'name' => '',
            'configuration' => '',
            'rows' => [
                [
                    'parameters' => [
                        'someParameter' => 'value',
                    ],
                ],
                [
                    'configuration' => [
                        'parameters' => [
                            'someParameter' => 'value',
                        ],
                    ],
                ],
                [
                    'configuration' => [
                        'parameters' => [
                            'objects' => [
                                [
                                    'hasNoName' => 'noName',
                                ],
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

        $decorator = new ComponentConfigurationRowsDecorator();

        self::assertFalse($decorator->supports($operationImport));
    }

    public function testSupportsValid(): void
    {
        $task = $this->getExampleOrchstrationTask();

        $configuration = [
            'name' => '',
            'configuration' => '',
            'rows' => [
                [
                    'configuration' => [
                        'parameters' => [
                            'objects' => [
                                [
                                    'name' => 'someName',
                                ],
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

        $decorator = new ComponentConfigurationRowsDecorator();

        self::assertTrue($decorator->supports($operationImport));
    }
}
