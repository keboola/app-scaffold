<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation;

use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\Operation\CreateOrchestrationOperation;
use Keboola\ScaffoldApp\OperationConfig\CreateOrchestrationOperationConfig;
use Keboola\StorageApi\Options\Components\Configuration;

class CreateOrchestrationOperationTest extends BaseOperationTestCase
{
    public function testExecute(): void
    {
        $executionMock = self::getExecutionContextMock();
        $apiClientStoreMock = self::getApiClientStore();
        $orchestratorApiClient = $this->getMockOrchestrationApiClient();
        $orchestratorApiClient->method('createOrchestration')->willReturn(
            ['id' => 'createdOrchestrationId']
        );
        $apiClientStoreMock->method('getOrchestrationApiClient')->willReturn($orchestratorApiClient);

        $operation = new CreateOrchestrationOperation($apiClientStoreMock);

        $operationConfig = [
            'payload' => [
                'name' => 'Test Orchestration',
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
        $executionMock->finishOperation(
            'op1',
            CreateConfigurationOperation::class,
            (new Configuration())->setConfigurationId('1')
        );

        $operation->execute($config, $executionMock);
        $orchestrationId = $executionMock->getFinishedOperationData('orch1');
        self::assertEquals('createdOrchestrationId', $orchestrationId);
    }
}
