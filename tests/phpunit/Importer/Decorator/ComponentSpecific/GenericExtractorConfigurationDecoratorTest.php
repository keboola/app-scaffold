<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer\Decorator\ComponentSpecific;

use Keboola\ScaffoldApp\Importer\Decorator\ComponentSpecific\GenericExtractorConfigurationDecorator;
use Keboola\ScaffoldApp\Importer\OperationImportFactory;
use Keboola\ScaffoldApp\Tests\Importer\ImporterBaseTestCase;
use Symfony\Component\Console\Output\NullOutput;

class GenericExtractorConfigurationDecoratorTest extends ImporterBaseTestCase
{
    public function testGetDecoratedProjectImport(): void
    {
        $task = $this->getExampleOrchestrationTask();

        $operationImport = OperationImportFactory::createOperationImport(
            [
                'name' => '',
                'configuration' => [
                    'parameters' => [
                        'config' => [
                            'jobs' => [
                                [
                                    'dataType' => 'table',
                                    'children' => [
                                        [
                                            'dataType' => 'table2',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'rows' => [],
            ],
            $task,
            'scaffoldId',
            new NullOutput
        );

        self::assertSame([
            [
                'definition' => [
                    'component' => 'keboola.processor-create-manifest',
                ],
                'parameters' => [
                    'delimiter' => ',',
                    'enclosure' => '"',
                    'incremental' => false,
                    'primary_key' => [],
                    'columns_from' => 'header',
                ],
            ],
            [
                'definition' => [
                    'component' => 'keboola.processor-skip-lines',
                ],
                'parameters' => [
                    'lines' => 1,
                    'direction_from' => 'top',
                ],
            ],
            [
                'definition' => [
                    'component' => 'keboola.processor-add-metadata',
                ],
                'parameters' => [
                    'tables' => [
                        [
                            'table' => 'table',
                            'metadata' => [
                                [
                                    'key' => 'bdm.scaffold.table.tag',
                                    'value' => 'scaffoldId.internal.inKeboolaComponentTable',
                                ],
                                [
                                    'key' => 'scaffold.id',
                                    'value' => 'scaffoldId',
                                ],
                            ],
                        ],
                        [
                            'table' => 'table2',
                            'metadata' => [
                                [
                                    'key' => 'bdm.scaffold.table.tag',
                                    'value' => 'scaffoldId.internal.inKeboolaComponentTable2',
                                ],
                                [
                                    'key' => 'scaffold.id',
                                    'value' => 'scaffoldId',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $operationImport->getPayload()['configuration']['processors']['after']);
    }

    public function testNotSupports(): void
    {
        $task = $this->getExampleOrchestrationTask();

        $operationImport = OperationImportFactory::createOperationImport(
            [
                'name' => '',
                'configuration' => [
                    'parameters' => [
                        'config' => [
                            'jobs' => [],
                        ],
                    ],
                ],
                'rows' => [],
            ],
            $task,
            'scaffoldId',
            new NullOutput
        );

        $decorator = new GenericExtractorConfigurationDecorator(new NullOutput);

        self::assertFalse($decorator->supports($operationImport));
    }

    public function testSupports(): void
    {
        $task = $this->getExampleOrchestrationTask();

        $operationImport = OperationImportFactory::createOperationImport(
            [
                'name' => '',
                'configuration' => [
                    'parameters' => [
                        'config' => [
                            'jobs' => [
                                [
                                    'dataType' => 'table',
                                ],
                            ],
                        ],
                    ],
                ],
                'rows' => [],
            ],
            $task,
            'scaffoldId',
            new NullOutput
        );

        $decorator = new GenericExtractorConfigurationDecorator(new NullOutput);

        self::assertTrue($decorator->supports($operationImport));

        $operationImport = OperationImportFactory::createOperationImport(
            [
                'name' => '',
                'configuration' => [
                    'parameters' => [
                        'config' => [
                            'jobs' => [
                                [
                                    'children' => [
                                        [
                                            'dataType' => 'table',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'rows' => [],
            ],
            $task,
            'scaffoldId',
            new NullOutput
        );

        $decorator = new GenericExtractorConfigurationDecorator(new NullOutput);

        self::assertTrue($decorator->supports($operationImport));
    }
}
