<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Exception;
use Keboola\Component\BaseComponent;
use Keboola\Component\JsonHelper;
use Keboola\Component\UserException;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\StorageApi\Client;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Filesystem\Filesystem;

class Component extends BaseComponent
{
    private const SYRUP_SERVICE_ID = 'syrup';

    protected function run(): void
    {
        /** @var Config $config */
        $config = $this->getConfig();
        //only one scaffold can be passed in parameters
        $scaffoldParameters = $config->getScaffoldInputs();
        $scaffoldConfiguration = $this->getScaffoldConfiguration($config->getScaffoldName());

        $client = new Client(
            [
                'token' => getenv('KBC_TOKEN'),
                'url' => getenv('KBC_URL'),
                'logger' => $this->getLogger(),
            ]
        );
        $orchestrationApiClient = OrchestratorClient::factory([
            'url' => $this->getSyrupApiUrl($client),
            'token' => getenv('KBC_TOKEN'),
        ]);

        $encryptionClient = EncryptionClient::createForStorageApi($client);

        $app = new App(
            $scaffoldConfiguration,
            $scaffoldParameters,
            $client,
            $orchestrationApiClient,
            $encryptionClient,
            $this->getLogger()
        );
        $app->run();
    }

    public function getSyncActions(): array
    {
        return [
            'listScaffolds' => 'actionListScaffolds',
            'useScaffold' => 'actionUseScaffold',
        ];
    }

    public function actionListScaffolds(): array
    {
        return [
            [
                'id' => 'PassThroughTest',
                'author' => 'John Doe',
                'description' => 'Sample Description',
                'inputs' => [
                    [
                        'id' => 'connectionWriter',
                        'componentId' => 'keboola.wr-storage',
                        'schema' => [
                            'type' => 'object',
                            'required' => ['#token'],
                            'properties' => [
                                '#token' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => 'snowflakeExtractor',
                        'componentId' => 'keboola.ex-snowflake',
                        'schema' => [
                            'type' => 'object',
                            'required' => ['db'],
                            'properties' => [
                                'db' => [
                                    'type' => 'object',
                                    'required' => ['host', 'user', '#password', 'database', 'schema', 'warehouse'],
                                    'properties' => [
                                        'host' => [
                                            'type' => 'string',
                                        ],
                                        'user' => [
                                            'type' => 'string',
                                        ],
                                        'schema' => [
                                            'type' => 'string',
                                        ],
                                        'database' => [
                                            'type' => 'string',
                                        ],
                                        '#password' => [
                                            'type' => 'string',
                                        ],
                                        'warehouse' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function actionUseScaffold(): array
    {
        return [
            'id' => 'PassThroughTest',
            'configurations' => [
                [
                    'id' => '12345',
                    'componentId' => 'keboola.ex-db-snowflake',
                    'actionRequired' => 'oauth',
                ],
                [
                    'id' => '123456',
                    'componentId' => 'keboola.wr-storage',
                ],
                [
                    'id' => '12345678',
                    'component' => 'orchestrator',
                    'actionRequired' => 'setSchedule',
                ],
            ],
        ];
    }

    private function getSyrupApiUrl(Client $sapiClient): string
    {
        $index = $sapiClient->indexAction();
        foreach ($index['services'] as $service) {
            if ($service['id'] === self::SYRUP_SERVICE_ID) {
                return $service['url'] . '/orchestrator';
            }
        }
        $tokenData = $sapiClient->verifyToken();
        throw new UserException(sprintf('Syrup not found in %s region', $tokenData['owner']['region']));
    }

    // load scaffold config.json file
    private function getScaffoldConfiguration(string $scaffoldName): array
    {
        $scaffoldFolder = __DIR__ . '/../scaffolds/' . $scaffoldName;
        $scaffoldConfigFile = $scaffoldFolder . '/scaffold.json';
        $fs = new Filesystem();
        if (!$fs->exists($scaffoldConfigFile)) {
            throw new Exception(sprintf('Scaffold name: %s missing scaffold.json configuration file.', $scaffoldName));
        }
        return JsonHelper::readFile($scaffoldConfigFile);
    }
}
