<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests;

use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\ScaffoldApp\App;
use Keboola\ScaffoldApp\EncryptionClient;
use Keboola\StorageApi\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class AppTest extends TestCase
{
    public function testRunSuccess(): void
    {
        $posts = [];
        $orchestrations = [];

        $app = $this->createApp(
            [
                'operations' => [
                    [
                        'operation' => 'create.configuration',
                        'id' => 'ex01',
                        'KBCComponentId' => 'ex01',
                        'payload' => [
                            'name' => 'ex01',
                        ],
                    ],
                    [
                        'operation' => 'create.configrows',
                        'refConfigId' => 'ex01',
                        'rows' => [
                            [
                                'name' => 'row01',
                                'configuration' => [],
                            ],
                        ],
                    ],
                    [
                        'operation' => 'create.orchestration',
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
            function ($orchestrationName, $options) use (&$orchestrations
            ): array {
                $orchestrations[$orchestrationName] = $options;

                return ['id' => 1]; // return orchstration id
            }
        );

        $app->run();

        $this->assertArrayHasKey('storage/components/ex01/configs', $posts);
        $this->assertArrayHasKey('storage/components/ex01/configs/1/rows', $posts);
        $this->assertArrayHasKey('orch01', $orchestrations);
    }

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

        $sapiClientMock->method('verifyToken')
            ->willReturn(['owner' => ['id' => '1']]);

        $sapiClientMock->method('apiPost')
            ->willReturnCallback($configurationCallback);

        /** @var OrchestratorClient|MockObject $orchestratorApiMock */
        $orchestratorApiMock = self::createMock(OrchestratorClient::class);

        $orchestratorApiMock->method('createOrchestration')
            ->willReturnCallback($orchestrationCallback);

        /** @var EncryptionClient|MockObject $encyptionApiMock */
        $encyptionApiMock = self::createMock(EncryptionClient::class);

        return new App(
            $scaffoldStaticConfiguration,
            $scaffoldParameters,
            $sapiClientMock,
            $orchestratorApiMock,
            $encyptionApiMock,
            new NullLogger()
        );
    }
}
