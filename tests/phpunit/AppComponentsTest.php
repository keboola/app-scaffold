<?php

declare(strict_types=1);

namespace MyComponent\Tests;

use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\StorageApi\Components;
use Keboola\StorageApi\Options\Components\Configuration;
use Keboola\StorageApi\Options\Components\ConfigurationRow;
use MyComponent\App;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class AppComponentsTest extends TestCase
{
    private function createApp(
        array $scaffoldStaticConfiguration,
        array $scaffoldParameters,
        callable $configurationCallback,
        callable $rowsConfigurationCallback
    ): App {
        /** @var Components|MockObject $sapiClientMock */
        $sapiClientMock = $this->getMockBuilder(Components::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sapiClientMock->method('addConfiguration')
            ->willReturnCallback($configurationCallback);

        $sapiClientMock->method('addConfigurationRow')
            ->willReturnCallback($rowsConfigurationCallback);

        /** @var OrchestratorClient|MockObject $orchestratorApiMock */
        $orchestratorApiMock = self::createMock(OrchestratorClient::class);

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
        $componentsCreated = [];
        $rowsCreated = [];

        $app = $this->createApp(
            [
                'components' => [
                    ExampleConfig::EXTRACTOR,
                    ExampleConfig::TRANSFORMATION,
                    ExampleConfig::WRITER,
                    ExampleConfig::APPLICATION,
                ],
            ],
            array_merge([/**Extra parameters*/], ExampleParameters::WRITER_SNOWFLAKE),
            function ($arg) use (&$componentsCreated): void {
                /** @var Configuration $arg */
                $componentsCreated[$arg->getComponentId()] = [$arg->getConfiguration()];
            },
            function ($arg) use (&$rowsCreated): void {
                /** @var ConfigurationRow $arg */
                $rowsCreated[$arg->getName()] = [$arg->getConfiguration()];
            }
        );

        $app->createComponentsConfigurations();

        $this->assertArrayHasKey(ExampleConfig::EXTRACTOR['sapiComponentId'], $componentsCreated);
        $this->assertArrayHasKey(ExampleConfig::TRANSFORMATION['sapiComponentId'], $componentsCreated);
        $this->assertArrayHasKey(ExampleConfig::WRITER['sapiComponentId'], $componentsCreated);
        $this->assertArrayHasKey(ExampleConfig::APPLICATION['sapiComponentId'], $componentsCreated);

        $this->assertSame(
            [
                ExampleConfig::EXTRACTOR['sapiComponentId'] => [[]],
                ExampleConfig::TRANSFORMATION['sapiComponentId'] => [[]],
                ExampleConfig::WRITER['sapiComponentId'] =>
                    [
                        array_merge_recursive(
                            ExampleConfig::WRITER['configuration'],
                            ExampleParameters::WRITER_SNOWFLAKE[
                                $this->normalizeId(ExampleConfig::WRITER['sapiComponentId'])
                            ]
                        ),
                    ],
                ExampleConfig::APPLICATION['sapiComponentId'] => [ExampleConfig::APPLICATION['configuration']],
            ],
            $componentsCreated
        );
    }

    private function normalizeId(string $id): string
    {
        return str_replace('-', '_', $id);
    }
}
