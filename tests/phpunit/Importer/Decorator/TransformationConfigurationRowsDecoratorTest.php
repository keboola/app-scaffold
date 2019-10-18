<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer\Decorator;

use Keboola\ScaffoldApp\Importer\Decorator\TransformationConfigurationRowsDecorator;
use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\OperationImportFactory;
use Keboola\ScaffoldApp\Tests\Importer\ImporterBaseTestCase;
use Symfony\Component\Console\Output\NullOutput;

class TransformationConfigurationRowsDecoratorTest extends ImporterBaseTestCase
{
    public function testGetDecoratorProjectImport(): void
    {
        $task = $this->getExampleOrchestrationTask();
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
                                'destination' => 'account',
                                'datatypes' => [],
                                'whereColumn' => '',
                                'whereValues' => [],
                                'whereOperator' => 'eq',
                                'columns' => [],
                                'loadType' => 'clone',
                                'source' => 'out.c-transformation.account',
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
                                'destination' => 'out.c-BucketName.account2',
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
            'scaffold_Id',
            new NullOutput
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
                                    'scaffold_Id.internal.inSalesforceAccount',
                            ],
                            '__SCAFFOLD_CHECK__.source' => 'in.c-scaffoldId.salesforceAccount',
                            '__SCAFFOLD_CHECK__.original_source' => 'in.c-salesforce.account',
                        ],
                    ],
                    'output' => [
                        [
                            'datatypes' => [],
                            'whereColumn' => '',
                            'whereValues' => [],
                            'whereOperator' => 'eq',
                            'columns' => [],
                            'loadType' => 'clone',
                            'metadata' => [
                                [
                                    'key' => 'bdm.scaffold.table.tag',
                                    'value' => 'scaffold_Id.internal.account',
                                ],
                            ],
                            'destination' => 'account',
                            '__SCAFFOLD_CHECK__original_destination' => 'account',
                        ],
                    ],
                ],
            ],
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
                                '__SCAFFOLD_CHECK__.value' => 'scaffold_Id.internal.outTransformationAccount',
                            ],
                            '__SCAFFOLD_CHECK__.source' => 'out.c-scaffoldId.transformationAccount',
                            '__SCAFFOLD_CHECK__.original_source' => 'out.c-transformation.account',
                        ],
                    ],
                    'output' => [
                        [
                            'datatypes' => [],
                            'whereColumn' => '',
                            'whereValues' => [],
                            'whereOperator' => 'eq',
                            'columns' => [],
                            'loadType' => 'clone',
                            'metadata' => [
                                [
                                    'key' => 'bdm.scaffold.table.tag',
                                    'value' => 'scaffold_Id.internal.account',
                                ],
                            ],
                            'destination' => 'account',
                            '__SCAFFOLD_CHECK__original_destination' => 'account',
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
                            'datatypes' => [],
                            'whereColumn' => '',
                            'whereValues' => [],
                            'whereOperator' => 'eq',
                            'columns' => [],
                            'loadType' => 'clone',
                            'metadata' => [
                                [
                                    'key' => 'bdm.scaffold.table.tag',
                                    'value' => 'scaffold_Id.internal.outBucketNameAccount2',
                                ],
                            ],
                            'destination' => 'out.c-scaffoldId.bucketNameAccount2',
                            '__SCAFFOLD_CHECK__original_destination' => 'out.c-BucketName.account2',
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame($expectedRows, $operationImport->getConfigurationRows());
    }

    public function testSupportsEmptyRows(): void
    {
        $task = $this->getExampleOrchestrationTask();

        $operationImport = OperationImportFactory::createOperationImport(
            [
                'name' => '',
                'configuration' => '',
                'rows' => [],
            ],
            $task,
            'scaffoldId',
            new NullOutput
        );

        $decorator = new TransformationConfigurationRowsDecorator(new NullOutput);

        self::assertFalse($decorator->supports($operationImport));
    }

    public function testSupportsNotTransformation(): void
    {
        $task = $this->getExampleOrchestrationTask();

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
            'scaffoldId',
            new NullOutput
        );

        $decorator = new TransformationConfigurationRowsDecorator(new NullOutput);

        self::assertFalse($decorator->supports($operationImport));
    }

    public function testSupportsValid(): void
    {
        $task = $this->getExampleOrchestrationTask();
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
            'scaffoldId',
            new NullOutput
        );

        $decorator = new TransformationConfigurationRowsDecorator(new NullOutput);

        self::assertTrue($decorator->supports($operationImport));
    }
}
