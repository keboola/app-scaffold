<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\SyncActions\UseScaffoldExecutionContext;

use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\Operation\CreateConfigurationRowsOperation;
use Keboola\ScaffoldApp\Operation\CreateOrchestrationOperation;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContext;
use Keboola\ScaffoldApp\Operation\OperationsQueue;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\TestCase;

class ExecutionContextTest extends TestCase
{
    public function testFinishOperation(): void
    {
        $executionContext = $this->getEmptyExecutionContext();
        $executionContext->getOperationsQueue()->finishOperation('id', CreateOrchestrationOperation::class, ['data']);
        self::assertSame(['data'], $executionContext->getOperationsQueue()->getFinishedOperationData('id'));
        self::assertCount(1, $executionContext->getFinishedOperationsResponse());
    }

    private function getEmptyExecutionContext(): ExecutionContext
    {
        return new ExecutionContext([], [], '', new OperationsQueue());
    }

    public function testGetFinishedOperationDataMissingReference(): void
    {
        $executionContext = $this->getEmptyExecutionContext();
        $executionContext->getOperationsQueue()->finishOperation('id', CreateConfigurationOperation::class, ['data']);

        self::expectException(\Throwable::class);
        self::expectExceptionMessage('Operation "NoId" was not finished or it\'s wrongly configured.');
        $executionContext->getOperationsQueue()->getFinishedOperationData('NoId');
    }

    public function testGetFinishedOperationsResponse(): void
    {
        $configuration = $this->getMockBuilder(Configuration::class)
            ->setMethods(['getConfigurationId'])
            ->disableOriginalConstructor()
            ->getMock();
        $configuration->method('getConfigurationId')
            ->willReturnOnConsecutiveCalls('123', '345');
        $executionContext = $this->getEmptyExecutionContext();
        $executionContext->getOperationsQueue()
            ->finishOperation('id1', CreateConfigurationOperation::class, $configuration);
        $executionContext->getOperationsQueue()
            ->finishOperation('id2', CreateOrchestrationOperation::class, '567');
        $executionContext->getOperationsQueue()
            ->finishOperation('id3', CreateConfigurationRowsOperation::class, ['data3']);
        $executionContext->getOperationsQueue()
            ->finishOperation(
                'id4',
                CreateConfigurationOperation::class,
                $configuration,
                [
                    'foo',
                    'bar',
                ]
            );
        self::assertSame(
            [
                [
                    'id' => 'id1',
                    'configurationId' => '123',
                    'userActions' => [],
                ],
                [
                    'id' => 'id2',
                    'configurationId' => '567',
                    'userActions' => [],
                ],
                [
                    'id' => 'id4',
                    'configurationId' => '345',
                    'userActions' => ['foo', 'bar'],
                ],
            ],
            $executionContext->getFinishedOperationsResponse()
        );
    }

    public function testGetScaffoldDefinitionClass(): void
    {
        $executionContext = new ExecutionContext(
            [],
            [],
            __DIR__ . '/../../mock/scaffolds/PassThroughTest',
            new OperationsQueue()
        );
        $class = $executionContext->getScaffoldDefinitionClass();
        self::assertEquals('Keboola\Scaffolds\PassThroughTest\ScaffoldDefinition', $class);
    }

    public function testGetScaffoldDefinitionClassNoClass(): void
    {
        $executionContext = new ExecutionContext(
            [],
            [],
            __DIR__ . '/../../mock/scaffolds/PassThroughTestNoDefinition',
            new OperationsQueue()
        );
        $class = $executionContext->getScaffoldDefinitionClass();
        self::assertNull($class);
    }

    public function testGetScaffoldInputs(): void
    {
        $executionContext = new ExecutionContext([], ['someInput'], '', new OperationsQueue());
        self::assertSame(['someInput'], $executionContext->getScaffoldInputs());
    }

    public function testGetSchemaForOperation(): void
    {
        $manifest = [
            'inputs' => [
                [
                    'id' => 'op1',
                    'required' => true,
                    'schema' => [
                        'type' => 'object',
                    ],
                ],
            ],
        ];
        $parameters = [
            'op1' => [
                'parameters' => [
                    'prop' => 'testProp',
                ],
            ],
        ];
        $executionContext = new ExecutionContext($manifest, $parameters, 'scaffoldId', new OperationsQueue());
        $schema = $executionContext->getSchemaForOperation('op1');
        self::assertSame(['type' => 'object'], $schema);
    }

    public function testGetUserInputsForOperation(): void
    {
        $manifest = [
            'inputs' => [
                [
                    'id' => 'op1',
                    'required' => true,
                    'schema' => [
                        'type' => 'object',
                    ],
                ],
            ],
        ];
        $parameters = [
            'op1' => [
                'parameters' => [
                    'prop' => 'testProp',
                ],
            ],
        ];
        $executionContext = new ExecutionContext($manifest, $parameters, 'scaffoldId', new OperationsQueue());
        $userInput = $executionContext->getUserInputsForOperation('op1');
        self::assertSame([
            'parameters' => [
                'prop' => 'testProp',
            ],
        ], $userInput);
    }
}
