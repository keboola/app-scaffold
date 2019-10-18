<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation;

use Keboola\Component\JsonHelper;
use Keboola\Component\UserException;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\ScaffoldApp\EncryptionClient;
use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\Operation\CreateConfigurationRowsOperation;
use Keboola\ScaffoldApp\Operation\CreateOrchestrationOperation;
use Keboola\ScaffoldApp\Operation\ExecutionContext;
use Keboola\StorageApi\Client as StorageApiClient;
use Keboola\StorageApi\Components as ComponentsApiClient;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ExecutionContextTest extends TestCase
{
    public function testFinishOperation(): void
    {
        $executionContext = $this->getEmptyExecutionContext();
        $executionContext->finishOperation('id', CreateOrchestrationOperation::class, ['data']);
        self::assertSame(['data'], $executionContext->getFinishedOperationData('id'));
        self::assertCount(1, $executionContext->getFinishedOperationsResponse());
    }

    private function getEmptyExecutionContext(): ExecutionContext
    {
        return new ExecutionContext([], [], '', new NullLogger);
    }

    public function testGetComponentsApiClient(): void
    {
        $executionContext = $this->getEmptyExecutionContext();
        self::assertInstanceOf(ComponentsApiClient::class, $executionContext->getComponentsApiClient());
    }

    public function testGetEncryptionApiClient(): void
    {
        $executionContext = $this->getEmptyExecutionContext();
        self::assertInstanceOf(EncryptionClient::class, $executionContext->getEncryptionApiClient());
    }

    public function testGetFinishedOperationDataMissingReference(): void
    {
        $executionContext = $this->getEmptyExecutionContext();
        $executionContext->finishOperation('id', CreateConfigurationOperation::class, ['data']);

        self::expectException(\Throwable::class);
        self::expectExceptionMessage('Operation "NoId" was not finished or it\'s wrongly configured.');
        $executionContext->getFinishedOperationData('NoId');
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
        $executionContext->finishOperation('id1', CreateConfigurationOperation::class, $configuration);
        $executionContext->finishOperation('id2', CreateOrchestrationOperation::class, '567');
        $executionContext->finishOperation('id3', CreateConfigurationRowsOperation::class, ['data3']);
        $executionContext->finishOperation('id4', CreateConfigurationOperation::class, $configuration, ['foo', 'bar']);
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

    public function testGetLogger(): void
    {
        $executionContext = $this->getEmptyExecutionContext();
        self::assertInstanceOf(NullLogger::class, $executionContext->getLogger());
    }

    public function testGetOrchestrationApiClient(): void
    {
        $executionContext = $this->getEmptyExecutionContext();
        self::assertInstanceOf(OrchestratorClient::class, $executionContext->getOrchestrationApiClient());
    }

    public function testGetScaffoldId(): void
    {
        $executionContext = new ExecutionContext([], [], 'scaffoldId', new NullLogger);
        self::assertSame('scaffoldId', $executionContext->getScaffoldId());
    }

    public function testGetScaffoldInputs(): void
    {
        $executionContext = new ExecutionContext([], ['someInput'], '', new NullLogger);
        self::assertSame(['someInput'], $executionContext->getScaffoldInputs());
    }

    public function testGetStorageApiClient(): void
    {
        $executionContext = $this->getEmptyExecutionContext();
        self::assertInstanceOf(StorageApiClient::class, $executionContext->getStorageApiClient());
    }

    public function testLoadOperations(): void
    {
        $manifest = [
            'inputs' => [
                [
                    'id' => 'op1',
                    'required' => true,
                ],
                [
                    'id' => 'op2',
                    'required' => false,
                ],
                [
                    'id' => 'op3',
                    'required' => false,
                ],
            ],
        ];

        $parameters = [
            'op1' => [],
            'op2' => [],
        ];

        $executionContext = new ExecutionContext($manifest, $parameters, 'scaffoldId', new NullLogger);
        $executionContext->loadOperations();
        self::assertSame([
            'op1',
            'op2',
        ], $executionContext->getOperationsToExecute());
        self::assertSame([
            'op1',
        ], $executionContext->getRequiredOperations());
    }

    public function testLoadOperationsFiles(): void
    {
        $parameters = [
            'snowflakeExtractor' => [],
            'connectionWriter' => [],
            'main' => [],
        ];

        $scaffoldFolder = __DIR__ . '/../mock/scaffolds/PassThroughTest';
        $manifest = JsonHelper::readFile($scaffoldFolder . '/manifest.json');
        $executionContext = new ExecutionContext($manifest, $parameters, $scaffoldFolder, new NullLogger);
        $executionContext->loadOperations();
        $executionContext->loadOperationsFiles();
        self::assertArrayHasKey('CreateConfiguration', $executionContext->getOperationsQueue());
        self::assertArrayHasKey('CreateConfigurationRows', $executionContext->getOperationsQueue());
        self::assertArrayHasKey('CreateOrchestration', $executionContext->getOperationsQueue());
        self::assertCount(2, $executionContext->getOperationsQueue()['CreateConfiguration']);
        self::assertCount(1, $executionContext->getOperationsQueue()['CreateConfigurationRows']);
        self::assertCount(1, $executionContext->getOperationsQueue()['CreateOrchestration']);
    }

    public function testLoadOperationsMissingRequiredOpearation(): void
    {
        $manifest = [
            'inputs' => [
                [
                    'id' => 'op1',
                    'required' => true,
                ],
                [
                    'id' => 'op2',
                    'required' => false,
                ],
                [
                    'id' => 'op3',
                    'required' => true,
                ],
            ],
        ];

        $parameters = [
            'op1' => [],
            'op2' => [],
        ];

        $executionContext = new ExecutionContext($manifest, $parameters, 'scaffoldId', new NullLogger);
        self::expectException(UserException::class);
        self::expectExceptionMessage('One or more required operations "op3" from scaffold id "scaffoldId" is missing.');
        $executionContext->loadOperations();
    }
}
