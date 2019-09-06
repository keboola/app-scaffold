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
use Keboola\Orchestrator\Client as OrchestratorClient;

class Component extends BaseComponent
{
    /**
     * @var array
     * aggreage configurations ids for use in orchestrations
     */
    private $configurationIdStorage = [];

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
            if ($component['type'] === 'orchestration') {
                // create orchestration
                $orchestratorClient = OrchestratorClient::factory([
                    'token' => getenv('KBC_TOKEN'),
                ]);
                $this->createOrchestrationConfiguration($component, $orchestratorClient);
            } else {
                // create component

                // primary used is id key which must be unique when two same components are used
                // otherwise sapiComponentId is used TODO: name this, maybe KBCComponentId?
                if (array_key_exists('id', $component)) {
                    /** @var string $componentId */
                    $componentId = str_replace('-', '_', $component['id']);
                } else {
                    /** @var string $componentId */
                    $componentId = str_replace('-', '_', $component['sapiComponentId']);
                }

                $parameters = null;
                if (array_key_exists($componentId, $scaffoldParameters)) {
                    $parameters = $scaffoldParameters[$componentId];
                }

                $this->createComponentConfiguration($component, $componentId, $componentApi, $parameters);
            }
        }
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

    private function createComponentConfiguration(
        array $componentConfig,
        string $componentId,
        Components $componentApi,
        ?array $parameters
    ): void {
        $this->getLogger()->info(
            sprintf(
                'Creating configuration for component %s with name %s',
                $componentConfig['sapiComponentId'],
                $componentConfig['name']
            )
        );
        $componentConfiguration = new Configuration;
        $componentConfiguration->setComponentId($componentConfig['sapiComponentId']);
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

        $this->getLogger()->info(
            sprintf(
                'Configuration for component %s with id %s created',
                $componentId,
                $response['id']
            )
        );

        // save id for use in orchestration
        $this->configurationIdStorage[$componentId] = $response['id'];

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

    private function createOrchestrationConfiguration(
        array $orchestratorConfig,
        OrchestratorClient $orchestratorApi
    ): void {
        $this->getLogger()->info(sprintf('Creating configuration for orchstration'));
        $orchestratorOptions = [];

        if (array_key_exists('tasks', $orchestratorConfig)) {
            $orchestratorOptions['tasks'] = [];
            foreach ($orchestratorConfig['tasks'] as $task) {
                /** @var string $componentConfigurationId */
                $componentConfigurationId = str_replace('-', '_', $task['id']);
                $orchestratorOptions['tasks'][] = array_merge(
                    $task,
                    [
                        'actionParameters' => [
                            'config' => $this->configurationIdStorage[$componentConfigurationId],
                        ],
                    ]
                );
            }
        }

        $orchestratorApi->createOrchestration($orchestratorConfig['name'], $orchestratorOptions);

        $this->getLogger()->info(sprintf('Orchestration created'));
    }
}
