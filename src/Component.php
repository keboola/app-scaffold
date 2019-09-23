<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Exception;
use Keboola\Component\BaseComponent;
use Keboola\Component\JsonHelper;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\StorageApi\Client;
use Symfony\Component\Filesystem\Filesystem;

class Component extends BaseComponent
{
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
            'token' => getenv('KBC_TOKEN'),
        ]);
        $app = new App(
            $scaffoldConfiguration,
            $scaffoldParameters,
            $client,
            $orchestrationApiClient,
            $this->getLogger()
        );
        $app->run();
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

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }
}
