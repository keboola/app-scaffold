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

        //only one scaffold can be passed in parameters
        $scaffoldParameters = $config->getScaffoldParameters();

        $fs = new Filesystem;
        $scaffoldConfiguration = $this->getScaffoldConfiguration($fs, $config->getScaffoldName());

        $client = new Client(
            [
                'token' => getenv('KBC_TOKEN'),
                'url' => getenv('KBC_URL'),
                'logger' => $this->getLogger(),
            ]
        );
        $componentApi = new Components($client);

        foreach ($scaffoldConfiguration['components'] as $component) {
            // component ID must be transformed "." are not allowed by symfony configuration and "-" are replaced by "_"
            /** @var string $transformetComponentId */
            $transformetComponentId = str_replace(['.', '-'], ['#', '_'], $component['id']);

            if (array_key_exists($transformetComponentId, $scaffoldParameters)) {
                $this->createComponentConfiguraion(
                    $component,
                    $componentApi,
                    $scaffoldParameters[$transformetComponentId]
                );
            } else {
                $this->createComponentConfiguraion($component, $componentApi);
            }
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

    // load scaffold config.json file
    private function getScaffoldConfiguration(Filesystem $fs, string $scaffoldName): array
    {
        $scaffoldFolder = '/code/scaffolds/' . $scaffoldName;
        $scaffoldConfigFile = $scaffoldFolder . '/config.json';

        if (!$fs->exists($scaffoldFolder)) {
            throw new Exception(sprintf('Scaffold name: %s does\'t exists.', $scaffoldName));
        }

        $fileHandler = new SplFileInfo($scaffoldConfigFile, '', 'config.json');

        return json_decode($fileHandler->getContents(), true);
    }

    private function createComponentConfiguraion(
        array $componentConfig,
        Components $componentApi,
        ?array $parameters = null
    ): void {
        $this->getLogger()->info(
            sprintf(
                'Creating configuration for component %s with name %s',
                $componentConfig['id'],
                $componentConfig['name']
            )
        );
        $componentConfiguration = new Configuration;
        $componentConfiguration->setComponentId($componentConfig['id']);
        $componentConfiguration->setName($componentConfig['name']);
        $componentConfiguration->setChangeDescription(
            sprintf(
                'KBC Scaffold component %s created',
                $componentConfig['name']
            )
        );

        //merge static configuration with dynamic parameters
        $configuration = [];
        if (array_key_exists('configuration', $componentConfig)) {
            $configuration = $componentConfig['configuration'];
        }
        if ($parameters !== null) {
            $configuration = array_merge_recursive($configuration, $parameters);
        }
        $componentConfiguration->setConfiguration($configuration);

        $response = $componentApi->addConfiguration($componentConfiguration);
        $componentConfiguration->setConfigurationId($response['id']);

        if (array_key_exists('rows', $componentConfig)) {
            foreach ($componentConfig['rows'] as $row) {
                $rowConfiguration = new ConfigurationRow($componentConfiguration);
                $rowConfiguration->setName($row['name']);
                $rowConfiguration->setConfiguration($row['configuration']);
                $rowConfiguration->setChangeDescription(sprintf('KBC Scaffold row %s added', $row['name']));
                $componentApi->addConfigurationRow($rowConfiguration);
            }
        }
    }
}
