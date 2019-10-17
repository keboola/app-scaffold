<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer\Decorator;

use Keboola\ScaffoldApp\Importer\Decorator\ComponentSpecific\ExSalesforceConfigurationRowsDecorator;
use Keboola\ScaffoldApp\Importer\OperationImportFactory;
use Keboola\ScaffoldApp\Tests\Importer\ImporterBaseTestCase;
use Symfony\Component\Console\Output\NullOutput;

class ExSalesforceConfigurationRowsDecoratorTest extends ImporterBaseTestCase
{
    public function testGetDecoratorProjectImport(): void
    {
        $task = $this->getExampleOrchstrationTask();
        $task->setComponent('htns.ex-salesforce');

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
            'Scaffold_Id',
            new NullOutput
        );

        self::assertEquals('htnsExSalesforceConfigurationName', $operationImport->getOperationId());
        self::assertEquals('htns.ex-salesforce', $operationImport->getComponentId());
        self::assertEquals(
            'htnsExSalesforceConfigurationName',
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
                                'definition' => [
                                    'component' => 'keboola.processor-add-metadata',
                                ],
                                'parameters' => [
                                    'tables' => [
                                        [
                                            'table' => 'sampleTableName.csv',
                                            'metadata' => [
                                                [
                                                    'key' => 'bdm.scaffold.table.tag',
                                                    '__SCAFFOLD_CHECK__value' =>
                                                        'Scaffold_Id.internal.inHtnsExSalesforce######SampleTableName',
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
            'scaffoldId',
            new NullOutput
        );

        $decorator = new ExSalesforceConfigurationRowsDecorator(new NullOutput);

        self::assertFalse($decorator->supports($operationImport));
    }

    public function testSupportsOtherComponent(): void
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
            'scaffoldId',
            new NullOutput
        );

        $decorator = new ExSalesforceConfigurationRowsDecorator(new NullOutput);

        self::assertFalse($decorator->supports($operationImport));
    }

    public function testSupportsValid(): void
    {
        $task = $this->getExampleOrchstrationTask();
        $task->setComponent('htns.ex-salesforce');

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
            'scaffoldId',
            new NullOutput
        );

        $decorator = new ExSalesforceConfigurationRowsDecorator(new NullOutput);

        self::assertTrue($decorator->supports($operationImport));
    }
}
