<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation;

use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\Operation\CreateOrchestrationOperation;
use Keboola\ScaffoldApp\OperationConfig\CreateOrchestrationOperationConfig;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class CreateOrchestrationOperationTest extends BaseOperationTestCase
{
    public function testExecute(): void
    {
        $executionMock = self::getExecutionContext([], [], 'dummy/scaffold_id');

        $orchestratorApiClient = $this->getMockOrchestrationApiClient();
        $orchestratorApiClient->expects(self::once())->method('createOrchestration')->willReturn(
            ['id' => 'createdOrchestrationId']
        );

        /** @var Client|MockObject $componentsClient */
        $componentsClient = $this->getMockComponentsApiClient();
        $componentsClient
            ->expects(self::once())->method('updateConfiguration')
            ->with(
                (new Configuration())
                    ->setDescription('Test Description |ScaffoldId: scaffold_id|')
                    ->setComponentId('orchestrator')
                    ->setConfigurationId('createdOrchestrationId')
            )
            ->willReturn([]);

        $apiClientStoreMock = self::getApiClientStore(null, $componentsClient, null, $orchestratorApiClient);

        $operation = new CreateOrchestrationOperation($apiClientStoreMock, new NullLogger);

        $operationConfig = [
            'payload' => [
                'name' => 'Test Orchestration',
                'description' => 'Test Description',
                'tasks' => [
                    [
                        'component' => 'kds-team.ex-reviewtrackers',
                        'operationReferenceId' => 'op1',
                        // reference to finished operation
                        'action' => 'run',
                        'timeoutMinutes' => null,
                        'active' => true,
                        'continueOnFailure' => false,
                        'phase' => null,
                    ],
                ],
            ],
        ];

        $config = CreateOrchestrationOperationConfig::create('orch1', $operationConfig, []);

        // mock finished CreateConfiguration
        $executionMock->getOperationsQueue()->finishOperation(
            'op1',
            CreateConfigurationOperation::class,
            (new Configuration())->setConfigurationId('1')
        );

        $operation->execute($config, $executionMock);
        $orchestrationId = $executionMock->getOperationsQueue()->getFinishedOperationData('orch1');
        self::assertEquals('createdOrchestrationId', $orchestrationId);
    }
}
