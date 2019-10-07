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
        $scaffoldParameters = $config->getScaffoldParameters();
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

    protected function loadConfig(): void
    {
        try {
            // first validate configuration without scaffolds name
            $generalConfig = new Config(
                $this->getRawConfig(),
                new ConfigDefinition(null)
            );
            // set config from configuration with scaffold name known
            $this->setConfig(new Config(
                $this->getRawConfig(),
                new ConfigDefinition($generalConfig)
            ));
        } catch (InvalidConfigurationException $e) {
            throw new UserException($e->getMessage(), 0, $e);
        }
    }
}
