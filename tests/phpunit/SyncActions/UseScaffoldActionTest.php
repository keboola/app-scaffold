<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\SyncActions;

use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldAction;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContextLoader;
use Keboola\ScaffoldApp\Tests\Operation\BaseOperationTestCase;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class UseScaffoldActionTest extends BaseOperationTestCase
{
    public function testAction(): void
    {
        $loader = new ExecutionContextLoader(
            [
                'connectionWriter' => [
                    'parameters' => [
                        '#token' => 'dummy',
                    ],
                ],
                'snowflakeExtractor' => [
                    'parameters' => [
                        'db' => [
                            'host' => 'dummy',
                            'user' => 'dummy',
                            '#password' => 'dummy',
                            'schema' => 'dummy',
                            'database' => 'dummy',
                            'warehouse' => 'dummy',
                        ],
                    ],
                ],
                'main' => [
                    'parameters' => [
                        'values' => null,
                    ],
                ],
            ],
            __DIR__ . '/../mock/scaffolds/PassThroughTestNoDefinition'
        );
        /** @var MockObject|Client $sapiClientMock */
        $sapiClientMock = self::createMock(Client::class);
        $sapiClientMock->expects(self::exactly(2))->method('verifyToken')->willReturn(['owner' => ['id' => 1]]);
        /** @var MockObject|Components $sapiClientMock */
        $componentsClientMock = self::createMock(Components::class);
        $componentsClientMock->expects(self::exactly(2))->method('addConfiguration')->willReturn(['id' => 1]);
        $componentsClientMock->expects(self::once())->method('addConfigurationRow')->willReturn(['id' => 1]);
        /** @var MockObject|OrchestratorClient $sapiClientMock */
        $orchestratorClientMock = self::createMock(OrchestratorClient::class);
        $orchestratorClientMock->expects(self::once())->method('createOrchestration')->willReturn(['id' => 1]);

        $clientStoreMock = $this->getApiClientStore(
            $sapiClientMock,
            $componentsClientMock,
            null,
            $orchestratorClientMock
        );

        $action = new UseScaffoldAction($loader->getExecutionContext(), $clientStoreMock, new NullLogger());
        $response = $action->run();
        foreach ($response as $finishedOperation) {
            self::assertArrayHasKey('id', $finishedOperation);
            self::assertArrayHasKey('configurationId', $finishedOperation);
            self::assertArrayHasKey('userActions', $finishedOperation);
        }
    }
}
