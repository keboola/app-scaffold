<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation;

use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\Operation\CreateOrchestrationOperation;
use Keboola\ScaffoldApp\Operation\FinishedOperationsStore;
use Keboola\ScaffoldApp\OperationConfig\CreateOrchestrationOperationConfig;
use Keboola\StorageApi\Options\Components\Configuration;
use Psr\Log\NullLogger;

class CreateOrchestrationOperationTest extends BaseOperationTestCase
{
    public function testExecute(): void
    {
        $orchestratorApiClient = $this->getMockOrchestrationApiClient();
        $orchestratorApiClient->method('createOrchestration')->willReturn(
            ['id' => 'createdOrchestrationId']
        );

        $operation = new CreateOrchestrationOperation(
            $orchestratorApiClient,
            new NullLogger()
        );

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
        $store = new FinishedOperationsStore();
        $store->add('op1', CreateConfigurationOperation::class, (new Configuration())->setConfigurationId('1'));

        $operation->execute($config, $store);
        $orchestrationId = $store->getOperationData('orch1');
        self::assertEquals('createdOrchestrationId', $orchestrationId);
    }
}
