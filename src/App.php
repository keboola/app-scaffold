<?php

declare(strict_types=1);

namespace MyComponent;

use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\StorageApi\Components;
use Keboola\StorageApi\Options\Components\Configuration;
use Keboola\StorageApi\Options\Components\ConfigurationRow;
use Psr\Log\LoggerInterface;

class App
{
    /**
     * @var array
     * aggreage configurations ids for use in orchestrations
     */
    private $configurationIdStorage = [];

    /**
     * @var array
     * Static config loaded from FS
     */
    private $scaffoldStaticConfiguration;

    /**
     * @var array
     * Variables from KBC
     */
    private $scaffoldParameters;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Components
     */
    private $componentsApiClient;

    /**
     * @var OrchestratorClient
     */
    private $orchestrationApiClient;

    public function __construct(
        array $scaffoldStaticConfiguration,
        array $scaffoldParameters,
        Components $componentsApiClient,
        OrchestratorClient $orchestrationApiClient,
        LoggerInterface $logger
    ) {
        $this->scaffoldStaticConfiguration = $scaffoldStaticConfiguration;
        $this->scaffoldParameters = $scaffoldParameters;
        $this->logger = $logger;
        $this->componentsApiClient = $componentsApiClient;
        $this->orchestrationApiClient = $orchestrationApiClient;
    }

    public function createComponentsConfigurations(): void
    {
        if (array_key_exists('components', $this->scaffoldStaticConfiguration)) {
            foreach ($this->scaffoldStaticConfiguration['components'] as $component) {
                // primary used is id key which must be unique when two same components are used
                // otherwise sapiComponentId is used TODO: name this, maybe KBCComponentId?
                if (array_key_exists('id', $component)) {
                    $componentId = $this->normalizeId($component['id']);
                } else {
                    $componentId = $this->normalizeId($component['sapiComponentId']);
                }

                $parameters = null;
                if (array_key_exists($componentId, $this->scaffoldParameters)) {
                    $parameters = $this->scaffoldParameters[$componentId];
                }

                $this->createComponentConfiguration($component, $componentId, $parameters);
            }
        }
    }

    public function createOrchestrations(): void
    {
        if (array_key_exists('orchestrations', $this->scaffoldStaticConfiguration)) {
            foreach ($this->scaffoldStaticConfiguration['orchestrations'] as $orchestration) {
                $this->createOrchestrationConfiguration($orchestration);
            }
        }
    }

    private function createOrchestrationConfiguration(
        array $orchestratorConfig
    ): void {
        $this->logger->info(sprintf('Creating configuration for orchstration'));
        $orchestratorOptions = [];

        if (array_key_exists('tasks', $orchestratorConfig)) {
            $orchestratorOptions['tasks'] = [];
            foreach ($orchestratorConfig['tasks'] as $task) {
                $componentConfigurationId = $this->normalizeId($task['id']);
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

        $response = $this->orchestrationApiClient->createOrchestration(
            $orchestratorConfig['name'],
            $orchestratorOptions
        );

        $this->logger->info(sprintf('Orchestration %s created', $response['id']));
    }

    private function createComponentConfiguration(
        array $componentConfig,
        string $componentId,
        ?array $parameters
    ): void {
        $this->logger->info(
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

        $response = $this->componentsApiClient->addConfiguration($componentConfiguration);
        $componentConfiguration->setConfigurationId($response['id']);

        $this->logger->info(
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
                $this->componentsApiClient->addConfigurationRow($rowConfiguration);
            }
        }
    }

    private function normalizeId(string $id): string
    {
        return str_replace('-', '_', $id);
    }
}
