<?php

declare(strict_types=1);

namespace MyComponent\Tests;

use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\StorageApi\Components;
use MyComponent\App;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class AppOrchestrationsTest extends TestCase
{
    private function createApp(
        array $scaffoldStaticConfiguration,
        array $scaffoldParameters,
        callable $createOrchestrationCallback
    ): App {
        /** @var Components|MockObject $sapiClientMock */
        $sapiClientMock = $this->getMockBuilder(Components::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var OrchestratorClient|MockObject $orchestratorApiMock */
        $orchestratorApiMock = self::createMock(OrchestratorClient::class);

        $orchestratorApiMock->method('createOrchestration')
            ->willReturnCallback($createOrchestrationCallback);

        return new App(
            $scaffoldStaticConfiguration,
            $scaffoldParameters,
            $sapiClientMock,
            $orchestratorApiMock,
            new NullLogger()
        );
    }

    public function testCreateComponentsConfigurations(): void
    {
        $orchestrationsCreated = [];

        $app = $this->createApp(
            [
                'orchestrations' => [
                    ExampleConfig::ORCHESTRATION,
                ],
            ],
            [],
            function ($name, $options = []) use (&$orchestrationsCreated): void {
                /** @var array $arg */
                $orchestrationsCreated[$name] = $options;
            }
        );

        $reflection = new \ReflectionClass($app);
        $property = $reflection->getProperty('configurationIdStorage');
        $property->setAccessible(true);
        $property->setValue($app, [
            'transformation1' => 1,
            'transformation2' => 2,
        ]);

        $app->createOrchestrations();

        $this->assertArrayHasKey(ExampleConfig::ORCHESTRATION['name'], $orchestrationsCreated);

        $expectedTasks = ExampleConfig::ORCHESTRATION['tasks'];
        $expectedTasks[0]['actionParameters'] = [
            'config' => 1,
        ];
        $expectedTasks[1]['actionParameters'] = [
            'config' => 2,
        ];

        $this->assertSame(
            [
                ExampleConfig::ORCHESTRATION['name'] => [
                    'tasks' => $expectedTasks,
                ],
            ],
            $orchestrationsCreated
        );
    }
}
