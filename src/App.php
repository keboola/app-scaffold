<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Exception;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\ScaffoldApp\ActionConfig\CreateCofigRowActionConfig;
use Keboola\ScaffoldApp\ActionConfig\CreateComponentActionConfig;
use Keboola\ScaffoldApp\ActionConfig\CreateOrchestrationActionConfig;
use Keboola\StorageApi\Client as StorageClient;
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
    }

    private function createOrchestration(CreateOrchestrationActionConfig $actionConfig): void
    {
        $this->logger->info('Creating configuration for orchstration');

        $actionConfig->populateOrchestrationTasksWithConfigurationIds($this->configurationIdStorage);

        $response = $this->orchestrationApiClient->createOrchestration(
            $actionConfig->getOrchestrationName(),
            $actionConfig->getTasks()
        );
        // save id, this for tests
        $this->configurationIdStorage[$actionConfig->getOrchestrationName()] = $response['id'];
        $this->logger->info(sprintf('Orchestration %s created', $response['id']));
    }

    private function createConfigRows(CreateCofigRowActionConfig $actionConfig): void
    {
        foreach ($actionConfig->getRows() as $row) {
            if (array_key_exists($actionConfig->getRefConfigId(), $this->configurationIdStorage)) {
                throw new Exception(sprintf(
                    'Action create.configrows refConfigId: %s wasn\'t create',
                    $actionConfig->getRefConfigId()
                ));
            }

            $this->storageApiClient->apiPost(
                sprintf(
                    'storage/components/%s/configs/%s/rows',
                    $this->configurationIdStorage[$actionConfig->getRefConfigId()]['component'],
                    $this->configurationIdStorage[$actionConfig->getRefConfigId()]['configId']
                ),
                $row
            );
        }
    }

    private function createComponent(CreateComponentActionConfig $actionConfig): void
    {
        $this->logger->info(
            sprintf(
                'Creating configuration for component %s with name %s',
                $actionConfig->getKBCComponentId(),
                $actionConfig->getComponentName()
            )
        );

        $response = $this->storageApiClient->apiPost(
            "storage/components/{$actionConfig->getKBCComponentId()}/configs",
            $actionConfig->getPayload()
        );

        $this->logger->info(
            sprintf(
                'Configuration for component %s with id %s created',
                $actionConfig->getKBCComponentId(),
                $response['id']
            )
        );

        if ($actionConfig->isSaveConfigId()) {
            // save id for use in orchestration
            $this->configurationIdStorage[$actionConfig->getId()] = [
                'configId' => $response['id'],
                'component' => $actionConfig->getKBCComponentId(),
            ];
        }
    }

    public function run(): void
    {
        foreach ($this->scaffoldStaticConfiguration['actions'] as $actionConfig) {
            switch ($actionConfig['action']) {
                case 'create.component':
                    $this->createComponent(
                        CreateComponentActionConfig::create($actionConfig, $this->scaffoldParameters)
                    );
                    break;
                case 'create.configrows':
                    $this->createConfigRows(
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
}
