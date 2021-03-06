<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer\Decorator;

use Keboola\ScaffoldApp\Importer\Decorator\ClearEncryptedParametersDecorator;
use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\OperationImportFactory;
use Keboola\ScaffoldApp\Tests\Importer\ImporterBaseTestCase;
use Symfony\Component\Console\Output\NullOutput;

class ClearEncryptedParametersDecoratorTest extends ImporterBaseTestCase
{
    public function testGetDecoratedProjectImport(): void
    {
        $task = $this->getExampleOrchestrationTask();
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
            'scaffoldId',
            new NullOutput
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
                    '#secret' => '__SCAFFOLD_CHECK__',
                    'notSecret' => 'preservedvalue',
                    'array' => ['x', 'x2'],
                    'recursive' => [
                        '#secret' => '__SCAFFOLD_CHECK__',
                        'notSecret' => 'preservedvalue',
                        'array' => ['x', 'x2'],
                        'recursive' => [
                            '#secret' => '__SCAFFOLD_CHECK__',
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
        $task = $this->getExampleOrchestrationTask();

        $operationImport = OperationImportFactory::createOperationImport(
            [
                'name' => '',
                'configuration' => [
                    'parameters' => [],
                ],
                'rows' => [],
            ],
            $task,
            'scaffoldId',
            new NullOutput
        );

        $decorator = new ClearEncryptedParametersDecorator(new NullOutput);

        self::assertTrue($decorator->supports($operationImport));
    }

    public function testSupportsNoParameters(): void
    {
        $task = $this->getExampleOrchestrationTask();

        $operationImport = OperationImportFactory::createOperationImport(
            [
                'name' => '',
                'configuration' => [],
                'rows' => [],
            ],
            $task,
            'scaffoldId',
            new NullOutput
        );

        $decorator = new ClearEncryptedParametersDecorator(new NullOutput);

        self::assertFalse($decorator->supports($operationImport));
    }
}
