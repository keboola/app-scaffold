<?php

declare(strict_types=1);

namespace MyComponent;

use Exception;
use Keboola\Component\BaseComponent;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class Component extends BaseComponent
{
    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }

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
        $componentsApiClient = new Components($client);

        $orchestrationApiClient = OrchestratorClient::factory([
            'token' => getenv('KBC_TOKEN'),
        ]);

        $app = new App(
            $scaffoldConfiguration,
            $scaffoldParameters,
            $componentsApiClient,
            $orchestrationApiClient,
            $this->getLogger()
        );

        $app->createComponentsConfigurations();
        $app->createOrchestrations();
    }

    // load scaffold config.json file
    private function getScaffoldConfiguration(string $scaffoldName): array
    {
        $scaffoldFolder = '/code/scaffolds/' . $scaffoldName;
        $scaffoldConfigFile = $scaffoldFolder . '/config.json';

        $fs = new Filesystem;
        if (!$fs->exists($scaffoldFolder)) {
            throw new Exception(sprintf('Scaffold name: %s does\'t exists.', $scaffoldName));
        }

        $fileHandler = new SplFileInfo($scaffoldConfigFile, '', 'config.json');

        return json_decode($fileHandler->getContents(), true);
    }
}
