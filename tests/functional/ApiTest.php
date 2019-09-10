<?php

declare(strict_types=1);

namespace MyComponent\FunctionalTests;

use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use MyComponent\App;
use MyComponent\Tests\ExampleConfig;
use MyComponent\Tests\ExampleParameters;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;

class ApiTest extends TestCase
{
    private function createComponentApiClient(): Components
    {
        $client = new Client(
            [
                'token' => getenv('KBC_TOKEN'),
                'url' => getenv('KBC_URL'),
                'logger' => new NullLogger(),
            ]
        );
        return new Components($client);
    }

    private function createOrchestrationApiClient(): OrchestratorClient
    {
        return OrchestratorClient::factory([
            'token' => getenv('KBC_TOKEN'),
        ]);
    }

    private function createApp(array $scaffoldStaticConfiguration, array $scaffoldParameters): App
    {
        return new App(
            $scaffoldStaticConfiguration,
            $scaffoldParameters,
            $this->createComponentApiClient(),
            $this->createOrchestrationApiClient(),
            new NullLogger()
        );
    }

    private function clearWorkspace(): void
    {
        $orchestrationApiClient = $this->createOrchestrationApiClient();
        $orchestrations = $orchestrationApiClient->getOrchestrations();
        foreach ($orchestrations as $orchestration) {
            if ($orchestration['name'] === ExampleConfig::ORCHESTRATION['name']) {
                $orchestrationApiClient->deleteOrchestration($orchestration['id']);
            }
        }

        $componentApiClient = $this->createComponentApiClient();
        $components = $componentApiClient->listComponents();
        foreach ($components as $component) {
            if ($component['id'] === 'orchestration') {
                continue;
            }
            foreach ($component['configurations'] as $configuration) {
                if (in_array(
                    $configuration['name'],
                    [
                        ExampleConfig::EXTRACTOR['name'],
                        ExampleConfig::TRANSFORMATION['name'],
                        ExampleConfig::TRANSFORMATION2['name'],
                        ExampleConfig::WRITER['name'],
                        ExampleConfig::APPLICATION['name'],
                    ]
                )) {
                    $componentApiClient->deleteConfiguration($component['id'], $configuration['id']);
                }
            }
        }
    }

    public function testCreateComponentsConfigurations(): void
    {
        $this->clearWorkspace();

        $app = $this->createApp(
            [
                'components' => [
                    ExampleConfig::EXTRACTOR,
                    ExampleConfig::TRANSFORMATION,
                    ExampleConfig::TRANSFORMATION2,
                    ExampleConfig::WRITER,
                    ExampleConfig::APPLICATION,
                ],
                'orchestrations' => [
                    ExampleConfig::ORCHESTRATION,
                ],
            ],
            array_merge([/**Extra parameters*/], ExampleParameters::WRITER_SNOWFLAKE)
        );

        $app->createComponentsConfigurations();
        $app->createOrchestrations();

        $reflectionClass = new ReflectionClass($app);
        $reflectionProperty = $reflectionClass->getProperty('configurationIdStorage');
        $reflectionProperty->setAccessible(true);

        $createdConfigurations = $reflectionProperty->getValue($app);

        /**
         *
         * Test if all configurations were created and saved in private property
         *
         */

        $this->assertArrayHasKey(
            $this->normalizeId(ExampleConfig::EXTRACTOR['sapiComponentId']),
            $createdConfigurations
        );
        $this->assertArrayHasKey(
            $this->normalizeId(ExampleConfig::TRANSFORMATION['id']),
            $createdConfigurations
        );
        $this->assertArrayHasKey(
            $this->normalizeId(ExampleConfig::TRANSFORMATION2['id']),
            $createdConfigurations
        );
        $this->assertArrayHasKey(
            $this->normalizeId(ExampleConfig::WRITER['sapiComponentId']),
            $createdConfigurations
        );
        $this->assertArrayHasKey(
            $this->normalizeId(ExampleConfig::APPLICATION['sapiComponentId']),
            $createdConfigurations
        );
        $this->assertArrayHasKey(
            $this->normalizeId(ExampleConfig::ORCHESTRATION['name']),
            $createdConfigurations
        );

        $componentApi = $this->createComponentApiClient();

        /**
         *
         * Test components configuration, excluding orchestrators
         *
         */

        $mockConfigurations = [
            ExampleConfig::EXTRACTOR,
            ExampleConfig::TRANSFORMATION,
            ExampleConfig::TRANSFORMATION2,
            ExampleConfig::WRITER,
            ExampleConfig::APPLICATION,
        ];

        foreach ($mockConfigurations as $mockConfiguration) {
            $savedComponentId = $mockConfiguration['sapiComponentId'];
            if (array_key_exists('id', $mockConfiguration)) {
                $savedComponentId = $mockConfiguration['id'];
            }
            $savedComponentId = $this->normalizeId($savedComponentId);

            $response = $componentApi->getConfiguration(
                $mockConfiguration['sapiComponentId'],
                $createdConfigurations[$savedComponentId]
            );

            $this->assertEquals($response['id'], $createdConfigurations[$savedComponentId]);
            $this->assertEquals($response['name'], $mockConfiguration['name']);
        }

        /**
         *
         * Test orchestration, this is separate to keep orchestrator config without id or sapiComponentId
         *
         */

        $mockOrchestrationConfigurations = [
            ExampleConfig::ORCHESTRATION,
        ];

        foreach ($mockOrchestrationConfigurations as $mockConfiguration) {
            $savedComponentId = $mockConfiguration['name'];
            $savedComponentId = $this->normalizeId($savedComponentId);

            $response = $componentApi->getConfiguration(
                'orchestrator',
                $createdConfigurations[$savedComponentId]
            );

            $this->assertEquals($response['id'], $createdConfigurations[$savedComponentId]);
            $this->assertEquals($response['name'], $mockConfiguration['name']);
        }
    }

    private function normalizeId(string $id): string
    {
        return str_replace('-', '_', $id);
    }
}
