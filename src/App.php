<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Exception;
use GuzzleHttp\Client;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\ScaffoldApp\OperationConfig\CreateCofigRowOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\CreateComponentConfigurationOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\CreateOrchestrationOperationConfig;
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

    /** @var EncryptionClient */
    private $encryptionApiClient;

    public function __construct(
        array $scaffoldStaticConfiguration,
        array $scaffoldParameters,
        StorageClient $storageApiClient,
        OrchestratorClient $orchestrationApiClient,
        EncryptionClient $encryptionApiClient,
        LoggerInterface $logger
    ) {
        $this->scaffoldStaticConfiguration = $scaffoldStaticConfiguration;
        $this->scaffoldParameters = $scaffoldParameters;
        $this->logger = $logger;
        $this->storageApiClient = $storageApiClient;
        $this->orchestrationApiClient = $orchestrationApiClient;
        $this->componentsApiClient = new Components($this->storageApiClient);
        $this->encryptionApiClient = $encryptionApiClient;
    }

    public function getCreatedConfigurations(): array
    {
        $response = [];
        foreach ($this->configurationIdStorage as $id => $createdItem) {
            // createdItem is id for orchestration
            $configurationId = $createdItem;
            if ($createdItem instanceof Configuration) {
                $configurationId = $createdItem->getConfigurationId();
            }
            $response[] = [
                'id' => $id,
                'configurationId' => $configurationId,
            ];
        }

        return $response;
    }

    public function run(): void
    {
        foreach ($this->scaffoldStaticConfiguration['operations'] as $operationConfig) {
            switch ($operationConfig['operation']) {
                case 'create.configuration':
                    $this->createComponentConfiguration(
                        CreateComponentConfigurationOperationConfig::create($operationConfig, $this->scaffoldParameters)
                    );
                    break;
                case 'create.configrows':
                    $this->createConfigurationRows(
                        CreateCofigRowOperationConfig::create($operationConfig, [])
                    );
                    break;
                case 'create.orchestration':
                    $this->createOrchestration(
                        CreateOrchestrationOperationConfig::create($operationConfig, [])
                    );
                    break;
                default:
                    throw new Exception(sprintf('Unknown action %s', $operationConfig['action']));
            }
        }
    }

    private function createComponentConfiguration(
        CreateComponentConfigurationOperationConfig $operationConfig
    ): void {
        $configuration = $operationConfig->getRequestConfiguration();

        $this->logger->info(
            sprintf(
                'Creating configuration for component %s with name %s',
                $configuration->getComponentId(),
                $configuration->getName()
            )
        );

        $tokenInfo = $this->storageApiClient->verifyToken();
        $configuration->setConfiguration($this->encryptionApiClient->encryptConfigurationData(
            $configuration->getConfiguration(),
            $configuration->getComponentId(),
            (string) $tokenInfo['owner']['id']
        ));
        $response = $this->componentsApiClient->addConfiguration($configuration);
        $configuration->setConfigurationId($response['id']);

        $this->logger->info(
            sprintf(
                'Configuration for component %s with id %s created',
                $configuration->getComponentId(),
                $configuration->getConfigurationId()
            )
        );

        // save id for use in orchestration
        $this->configurationIdStorage[$operationConfig->getId()] = $configuration;
    }

    private function createConfigurationRows(
        CreateCofigRowOperationConfig $operationConfig
    ): void {
        $this->logger->info(sprintf('Creating config rows for %s', $operationConfig->getRefConfigId()));

        if (!$this->isConfigurationCreated($operationConfig->getRefConfigId())) {
            throw new Exception(sprintf(
                'Configuration for component refConfigId: %s wasn\'t created or saved.',
                $operationConfig->getRefConfigId()
            ));
        }

        /** @var Configuration $componentConfiguration */
        $componentConfiguration = $this->configurationIdStorage[$operationConfig->getRefConfigId()];

        foreach ($operationConfig->getIterator($componentConfiguration) as $row) {
            $response = $this->componentsApiClient->addConfigurationRow($row);
            $this->logger->info(sprintf('Row for %s created', $response['id']));
        }
    }

    private function isConfigurationCreated(string $refConfigId): bool
    {
        return array_key_exists($refConfigId, $this->configurationIdStorage);
    }

    private function createOrchestration(
        CreateOrchestrationOperationConfig $operationConfig
    ): void {
        $this->logger->info('Creating configuration for orchstration');

        $operationConfig->populateOrchestrationTasksWithConfigurationIds($this->configurationIdStorage);
        $response = $this->orchestrationApiClient->createOrchestration(
            $operationConfig->getOrchestrationName(),
            $operationConfig->getPayload()
        );

        // save id, this for tests
        $this->configurationIdStorage[$operationConfig->getOrchestrationName()] = $response['id'];
        $this->logger->info(sprintf('Orchestration %s created', $response['id']));
    }
}
