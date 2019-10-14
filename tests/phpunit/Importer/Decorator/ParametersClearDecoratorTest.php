<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer\Decorator;

use Keboola\ScaffoldApp\Importer\Decorator\ParametersClearDecorator;
use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\OperationImportFactory;
use Keboola\ScaffoldApp\Tests\Importer\ImporterBaseTestCase;

class ParametersClearDecoratorTest extends ImporterBaseTestCase
{
    public function testGetDecoratedProjectImport(): void
    {
        $task = $this->getExampleOrchstrationTask();
        $task->setComponent('transformation');

        $configuration = [
            'name' => 'configurationName',
            'configuration' => [
                'parameters' => [
                    '#secret' => 'CryptedValue',
                    'notSecret' => 'preservedvalue',
                    'array' => ['x', 'x2'],
                    'recursive' => [
                        '#secret' => 'CryptedValue',
                        'notSecret' => 'preservedvalue',
                        'array' => ['x', 'x2'],
                        'recursive' => [
                            '#secret' => 'CryptedValue',
                            'notSecret' => 'preservedvalue',
                            'array' => ['x', 'x2'],
                        ],
                    ],
                ],
                'processors' => [
                    'after' => [],
                ],
            ],
            'rows' => [],
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

        $expectedPayload = [
            'name' => 'configurationName',
            'configuration' => [
                'parameters' => [
                    '#secret' => '',
                    'notSecret' => 'preservedvalue',
                    'array' => ['x', 'x2'],
                    'recursive' => [
                        '#secret' => '',
                        'notSecret' => 'preservedvalue',
                        'array' => ['x', 'x2'],
                        'recursive' => [
                            '#secret' => '',
                            'notSecret' => 'preservedvalue',
                            'array' => ['x', 'x2'],
                        ],
                    ],
                ],
                'processors' => [
                    'after' => [
                    ],
                ],
            ],
        ];

        self::assertSame($expectedPayload, $operationImport->getPayload());
    }

    public function testSupports(): void
    {
        $task = $this->getExampleOrchstrationTask();

        $operationImport = OperationImportFactory::createOperationImport(
            [
                'name' => '',
                'configuration' => [
                    'parameters' => [],
                ],
                'rows' => [],
            ],
            $task,
            'scaffoldId'
        );

        $decorator = new ParametersClearDecorator();

        self::assertTrue($decorator->supports($operationImport));
    }

    public function testSupportsNoParameters(): void
    {
        $task = $this->getExampleOrchstrationTask();

        $operationImport = OperationImportFactory::createOperationImport(
            [
                'name' => '',
                'configuration' => [],
                'rows' => [],
            ],
            $task,
            'scaffoldId'
        );

        $decorator = new ParametersClearDecorator();

        self::assertFalse($decorator->supports($operationImport));
    }
}
