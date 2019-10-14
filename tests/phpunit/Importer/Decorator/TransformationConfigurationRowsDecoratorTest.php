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

    public function testSupportsEmptyRows(): void
    {
        $task = $this->getExampleOrchstrationTask();

        $operationImport = OperationImportFactory::createOperationImport(
            [
                'name' => '',
                'configuration' => '',
                'rows' => [],
            ],
            $task
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
            $task
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
            $task
        );

        $decorator = new TransformationConfigurationRowsDecorator();

        self::assertTrue($decorator->supports($operationImport));
    }
}
