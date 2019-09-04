<?php

declare(strict_types=1);

namespace MyComponent;

use Exception;
use Keboola\Component\BaseComponent;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use Keboola\StorageApi\Options\Components\Configuration;
use Keboola\StorageApi\Options\Components\ConfigurationRow;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class Component extends BaseComponent
{
    protected function run(): void
    {
        /** @var Config $config */
        $config = $this->getConfig();

        $scaffoldImageParameters = $config->getValue(['image_parameters', 'scaffold']);

        $fs = new Filesystem;
        $scaffoldConfiguration = $this->getScaffoldConfiguration($fs, $scaffoldImageParameters);

        $client = new Client(
            [
                'token'  => getenv('KBC_TOKEN'),
                'url'    => getenv('KBC_URL'),
                'logger' => $this->getLogger(),
            ]
        );
        $componentApi = new Components($client);

        foreach ($scaffoldConfiguration['components'] as $component) {
            $this->createComponent($component, $componentApi);
        }
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }

    private function getScaffoldConfiguration(Filesystem $fs, array $scaffoldImageParameters): array
    {
        $scaffoldFolder = '/code/scaffolds/' . $scaffoldImageParameters['name'];
        $scaffoldConfigFile = $scaffoldFolder . '/config.json';

        if (!$fs->exists($scaffoldFolder)) {
            throw new Exception(sprintf('Scaffold name: %s does\'t exists.', $scaffoldImageParameters['name']));
        }

        $fileHandler = new SplFileInfo($scaffoldConfigFile, '', 'config.json');

        return json_decode($fileHandler->getContents(), true);
    }

    private function createComponent(array $component, Components $componentApi): void
    {
        $componentConfiguration = new Configuration;
        $componentConfiguration->setComponentId($component['id']);
        $componentConfiguration->setName($component['name']);
        $componentConfiguration->setChangeDescription(sprintf('KBC Scaffold component %s created', $component['name']));
        $response = $componentApi->addConfiguration($componentConfiguration);
        $componentConfiguration->setConfigurationId($response['id']);

        foreach ($component['rows'] as $row) {
            $rowConfiguration = new ConfigurationRow($componentConfiguration);
            $rowConfiguration->setName($row['name']);
            $rowConfiguration->setConfiguration($row['configuration']);
            $rowConfiguration->setChangeDescription(sprintf('KBC Scaffold row %s added', $row['name']));
            $componentApi->addConfigurationRow($rowConfiguration);
        }
    }
}
