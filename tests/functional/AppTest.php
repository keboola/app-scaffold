<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\FunctionalTests;

use Keboola\ScaffoldApp\App;
use Keboola\ScaffoldApp\EncryptionClient;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\TestCase;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Psr\Log\NullLogger;
use ReflectionClass;

class AppTest extends TestCase
{
    private const MOCK_CONFIG = [
        'operations' => [
            [
                'operation' => 'create.configuration',
                'id' => 'ex01',
                'KBCComponentId' => 'kds-team.ex-reviewtrackers',
                'saveConfigId' => true,
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
                            'component' => 'kds-team.ex-reviewtrackers',
                            'action' => 'run',
                            'timeoutMinutes' => null,
                            'active' => true,
                            'continueOnFailure' => false,
                            'phase' => 'Extraction',
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function testCreateComponentsConfigurations(): void
    {
        $this->clearWorkspace();
        $app = $this->createApp(
            self::MOCK_CONFIG,
            [
                'ex01' => [
                    'param1' => 'param1val',
                ],
            ]
        );
        $app->run();

        $reflectionClass = new ReflectionClass($app);
        $reflectionProperty = $reflectionClass->getProperty('configurationIdStorage');
        $reflectionProperty->setAccessible(true);
        $createdConfigurations = $reflectionProperty->getValue($app);

        //Test if configuration were created and saved in private property
        self::assertArrayHasKey(
            'ex01',
            $createdConfigurations
        );
        //Test if orchestration were created and saved in private property
        self::assertArrayHasKey(
            'orch01',
            $createdConfigurations
        );

        $componentApi = new Components($this->createStorageApiClient());

        // TEST created configurations
        /** @var Configuration $createdComponentConfig */
        $createdComponentConfig = $createdConfigurations['ex01'];
        $componentConfigurationReponse = $componentApi->getConfiguration(
            'kds-team.ex-reviewtrackers',
            $createdComponentConfig->getConfigurationId()
        );
        self::assertEquals($componentConfigurationReponse['id'], $createdComponentConfig->getConfigurationId());
        self::assertEquals($componentConfigurationReponse['name'], 'ex01');
        self::assertSame(['param1' => 'param1val'], $componentConfigurationReponse['configuration']);

        // TEST created orchestrations
        $orchestrationApi = $this->createOrchestrationApiClient();
        $orchestrationResponse = $orchestrationApi->getOrchestration($createdConfigurations['orch01']);

        self::assertEquals($orchestrationResponse['id'], $createdConfigurations['orch01']);
        self::assertEquals($orchestrationResponse['name'], 'orch01');
        self::assertSame(
            [
                [
                    'id' => $orchestrationResponse['tasks'][0]['id'],
                    'component' => 'kds-team.ex-reviewtrackers',
                    'action' => 'run',
                    'actionParameters' => [
                        'config' => $createdComponentConfig->getConfigurationId(),
                    ],
                    'timeoutMinutes' => null,
                    'active' => true,
                    'continueOnFailure' => false,
                    'phase' => 'Extraction',
                ],
            ],
            $orchestrationResponse['tasks']
        );

        $createdConfigurationsReponse = $app->getCreatedConfigurations();

        self::assertSame([
            [
                'id' => 'ex01',
                'configurationId' => $componentConfigurationReponse['id'],
            ],
            [
                'id' => 'orch01',
                'configurationId' => $orchestrationResponse['id'],
            ],
        ], $createdConfigurationsReponse);
    }

    private function clearWorkspace(): void
    {
        $orchestrationApiClient = $this->createOrchestrationApiClient();
        $orchestrations = $orchestrationApiClient->getOrchestrations();
        foreach ($orchestrations as $orchestration) {
            if ($orchestration['name'] === 'orch01') {
                $orchestrationApiClient->deleteOrchestration($orchestration['id']);
            }
        }
        $componentApiClient = new Components($this->createStorageApiClient());
        $components = $componentApiClient->listComponents();
        foreach ($components as $component) {
            if ($component['id'] === 'orchestration') {
                continue;
            }
            foreach ($component['configurations'] as $configuration) {
                if (in_array(
                    $configuration['name'],
                    [
                        'ex01',
                    ]
                )) {
                    $componentApiClient->deleteConfiguration($component['id'], $configuration['id']);
                }
            }
        }
    }

    private function createOrchestrationApiClient(): OrchestratorClient
    {
        return OrchestratorClient::factory([
            'token' => getenv('KBC_TOKEN'),
        ]);
    }

    private function createStorageApiClient(): Client
    {
        return new Client(
            [
                'token' => getenv('KBC_TOKEN'),
                'url' => getenv('KBC_URL'),
                'logger' => new NullLogger(),
            ]
        );
    }

    private function createApp(
        array $scaffoldStaticConfiguration,
        array $scaffoldParameters
    ): App {
        return new App(
            $scaffoldStaticConfiguration,
            $scaffoldParameters,
            $this->createStorageApiClient(),
            $this->createOrchestrationApiClient(),
            $this->createEncryptionApiClient(),
            new NullLogger()
        );
    }

    private function createEncryptionApiClient(): EncryptionClient
    {
        return EncryptionClient::createForStorageApi($this->createStorageApiClient());
    }
}
