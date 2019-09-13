<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Exception;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\ScaffoldApp\ActionConfig\CreateCofigRowActionConfig;
use Keboola\ScaffoldApp\ActionConfig\CreateComponentConfigurationActionConfig;
use Keboola\ScaffoldApp\ActionConfig\CreateOrchestrationActionConfig;
use Keboola\StorageApi\Client as StorageClient;
use Keboola\StorageApi\Components;
use Keboola\StorageApi\Options\Components\Configuration;
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
     * @var StorageClient
     */
    private $storageApiClient;

    /**
     * @var OrchestratorClient
     */
    private $orchestrationApiClient;

    /**
     * @var Components
     */
    private $componentsApiClient;

    public function __construct(
        array $scaffoldStaticConfiguration,
        array $scaffoldParameters,
        StorageClient $storageApiClient,
        OrchestratorClient $orchestrationApiClient,
        LoggerInterface $logger
    ) {
        $this->scaffoldStaticConfiguration = $scaffoldStaticConfiguration;
        $this->scaffoldParameters = $scaffoldParameters;
        $this->logger = $logger;
        $this->storageApiClient = $storageApiClient;
        $this->orchestrationApiClient = $orchestrationApiClient;
        $this->componentsApiClient = new Components($this->storageApiClient);
    }

    private function createOrchestration(CreateOrchestrationActionConfig $actionConfig): void
    {
        $this->logger->info('Creating configuration for orchstration');

        $actionConfig->populateOrchestrationTasksWithConfigurationIds($this->configurationIdStorage);
        $response = $this->orchestrationApiClient->createOrchestration(
            $actionConfig->getOrchestrationName(),
            $actionConfig->getPayload()
        );

        // save id, this for tests
        $this->configurationIdStorage[$actionConfig->getOrchestrationName()] = $response['id'];
        $this->logger->info(sprintf('Orchestration %s created', $response['id']));
    }

    private function createConfigurationRows(CreateCofigRowActionConfig $actionConfig): void
    {
        $this->logger->info(sprintf('Creating config rows for %s', $actionConfig->getRefConfigId()));

        if (!$this->isConfigurationCreated($actionConfig->getRefConfigId())) {
            throw new Exception(sprintf(
                'Configuration for component refConfigId: %s wasn\'t created or saved.',
                $actionConfig->getRefConfigId()
            ));
        }

        /** @var Configuration $componentConfiguration */
        $componentConfiguration = $this->configurationIdStorage[$actionConfig->getRefConfigId()];

        foreach ($actionConfig->getIterator($componentConfiguration) as $row) {
            $response = $this->componentsApiClient->addConfigurationRow($row);
            $this->logger->info(sprintf('Row for %s created', $response['id']));
        }
    }

    private function createComponentConfiguration(CreateComponentConfigurationActionConfig $actionConfig): void
    {
        $configuration = $actionConfig->getRequestConfiguration();

        $this->logger->info(
            sprintf(
                'Creating configuration for component %s with name %s',
                $configuration->getComponentId(),
                $configuration->getName()
            )
        );

        $response = $this->componentsApiClient->addConfiguration($configuration);
        $configuration->setConfigurationId($response['id']);

        $this->logger->info(
            sprintf(
                'Configuration for component %s with id %s created',
                $configuration->getComponentId(),
                $configuration->getConfigurationId()
            )
        );

        if ($actionConfig->isSaveConfigId()) {
            // save id for use in orchestration
            $this->configurationIdStorage[$actionConfig->getId()] = $configuration;
        }
    }

    public function run(): void
    {
        foreach ($this->scaffoldStaticConfiguration['actions'] as $actionConfig) {
            switch ($actionConfig['action']) {
                case 'create.configuration':
                    $this->createComponentConfiguration(
                        CreateComponentConfigurationActionConfig::create($actionConfig, $this->scaffoldParameters)
                    );
                    break;
                case 'create.configrows':
                    $this->createConfigurationRows(
                        CreateCofigRowActionConfig::create($actionConfig, null)
                    );
                    break;
                case 'create.orchestration':
                    $this->createOrchestration(
                        CreateOrchestrationActionConfig::create($actionConfig, null)
                    );
                    break;
                default:
                    throw new Exception(sprintf('Unknown action %s', $actionConfig['action']));
            }
        }
    }

    private function isConfigurationCreated(string $refConfigId): bool
    {
        return array_key_exists($refConfigId, $this->configurationIdStorage);
    }
}
