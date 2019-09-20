<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests;

use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\ScaffoldApp\App;
use Keboola\StorageApi\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Throwable;

class AppTest extends TestCase
{
    private function createApp(
        array $scaffoldStaticConfiguration,
        array $scaffoldParameters,
        callable $configurationCallback,
        callable $orchestrationCallback
    ): App {
        /** @var Client|MockObject $sapiClientMock */
        $sapiClientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sapiClientMock->method('apiPost')
            ->willReturnCallback($configurationCallback);

        /** @var OrchestratorClient|MockObject $orchestratorApiMock */
        $orchestratorApiMock = self::createMock(OrchestratorClient::class);

        $orchestratorApiMock->method('createOrchestration')
            ->willReturnCallback($orchestrationCallback);

        return new App(
            $scaffoldStaticConfiguration,
            $scaffoldParameters,
            $sapiClientMock,
            $orchestratorApiMock,
            new NullLogger()
        );
    }

    public function testRunSuccess(): void
    {
        $posts = [];
        $orchestrations = [];

        $app = $this->createApp(
            [
                'actions' => [
                    [
                        'action' => 'create.configuration',
                        'id' => 'ex01',
                        'KBCComponentId' => 'ex01',
                        'payload' => [
                            'name' => 'ex01',
                        ],
                    ],
                    [
                        'action' => 'create.configrows',
                        'refConfigId' => 'ex01',
                        'rows' => [
                            [
                                'name' => 'row01',
                                'configuration' => [],
                            ],
                        ],
                    ],
                    [
                        'action' => 'create.orchestration',
                        'payload' => [
                            'name' => 'orch01',
                            'tasks' => [
                                [
                                    'refConfigId' => 'ex01',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [ // parameters to merge
                'ex01' => [
                    'parameters' => [
                        'param1' => 'param1val',
                    ],
                ],
            ],
            function ($url, $payload) use (&$posts): array {
                $posts[$url] = $payload;

                return ['id' => 1]; // return config id
            },
            function ($orchestrationName, $options) use (&$orchestrations): array {
                $orchestrations[$orchestrationName] = $options;

                return ['id' => 1]; // return orchstration id
            }
        );

        $app->run();

        $this->assertArrayHasKey('storage/components/ex01/configs', $posts);
        $this->assertArrayHasKey('storage/components/ex01/configs/1/rows', $posts);
        $this->assertArrayHasKey('orch01', $orchestrations);
    }
}
