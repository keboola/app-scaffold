<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Exception;
use Keboola\Component\BaseComponent;
use Keboola\Component\JsonHelper;
use Keboola\Component\UserException;
use Keboola\Orchestrator\Client as OrchestratorApiClient;
use Keboola\ScaffoldApp\OperationConfig\CreateCofigurationRowsOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\CreateConfigurationOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\CreateOrchestrationOperationConfig;
use Keboola\ScaffoldApp\Operation\CreateConfigurationRowsOperation;
use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\Operation\CreateOrchestrationOperation;
use Keboola\ScaffoldApp\Operation\FinishedOperationsStore;
use Keboola\ScaffoldApp\Operation\OperationsConfig;
use Keboola\StorageApi\Client as StorageApiClient;
use Keboola\StorageApi\Components as ComponentsApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class Component extends BaseComponent
{
    public const SCAFFOLDS_DIR = __DIR__ . '/../scaffolds';

    public function actionListScaffolds(): array
    {
        $finder = new Finder();

        // CreateConfiguration
        $scaffolds = $finder->in(self::SCAFFOLDS_DIR)
            ->directories()->depth(0);

        $response = [];

        foreach ($scaffolds->getIterator() as $directory) {
            $response[$directory->getFilename()] = JsonHelper::readFile(sprintf(
                '%s/manifest.json',
                $directory->getPathname()
            ));
        }

        return $response;
    }

    public function actionUseScaffold(): array
    {
        /** @var Config $config */
        $config = $this->getConfig();
        $scaffoldInputs = $config->getScaffoldInputs();
        $scaffoldConfigurationFolder = $this->getScaffoldConfigurationFolder($config->getScaffoldName());

        $storageApiClient = new StorageApiClient(
            [
                'token' => getenv('KBC_TOKEN'),
                'url' => getenv('KBC_URL'),
                'logger' => $this->getLogger(),
            ]
        );

        $finishedOperations = $this->executeOperations(
            $scaffoldConfigurationFolder,
            $scaffoldInputs,
            $storageApiClient,
            OrchestratorClientFactory::createForStorageApi($storageApiClient),
            EncryptionClient::createForStorageApi($storageApiClient),
            new ComponentsApiClient($storageApiClient),
            $this->getLogger()
        );

        return $finishedOperations->getOperationsResponse();
    }

    private function getScaffoldConfigurationFolder(
        string $scaffoldName
    ): string {
        $scaffoldFolder = self::SCAFFOLDS_DIR . '/' . $scaffoldName;

        $fs = new Filesystem();
        if (!$fs->exists($scaffoldFolder)) {
            throw new Exception(sprintf('Scaffold "%s" not exists.', $scaffoldName));
        }

        return $scaffoldFolder;
    }

    private function executeOperations(
        string $scaffoldConfigurationFolder,
        array $scaffoldParameters,
        StorageApiClient $storageApiClient,
        OrchestratorApiClient $orchestrationApiClient,
        EncryptionClient $encryptionApiClient,
        ComponentsApiClient $componentsApiClient,
        LoggerInterface $logger
    ): FinishedOperationsStore {
        $operationsStore = new FinishedOperationsStore();

        // CreateConfiguration
        $operationsFiles = $this->getOperationsFiles(
            $scaffoldConfigurationFolder,
            OperationsConfig::CREATE_CONFIGURATION
        );
        foreach ($operationsFiles->getIterator() as $file) {
            $config = CreateConfigurationOperationConfig::create(
                $file->getBasename('.json'),
                JsonHelper::readFile($file->getPathname()),
                $scaffoldParameters
            );
            $operation = new CreateConfigurationOperation(
                $storageApiClient,
                $encryptionApiClient,
                $componentsApiClient,
                $logger
            );
            $operation->execute($config, $operationsStore);
        }

        // CreateConfigRows
        $operationsFiles = $this->getOperationsFiles(
            $scaffoldConfigurationFolder,
            OperationsConfig::CREATE_CONFIGURATION_ROWS
        );
        foreach ($operationsFiles->getIterator() as $file) {
            $config = CreateCofigurationRowsOperationConfig::create(
                $file->getBasename('.json'),
                JsonHelper::readFile($file->getPathname()),
                []
            );
            $operation = new CreateConfigurationRowsOperation($componentsApiClient, $logger);
            $operation->execute($config, $operationsStore);
        }

        // CreateOrchestration
        $operationsFiles = $this->getOperationsFiles(
            $scaffoldConfigurationFolder,
            OperationsConfig::CREATE_ORCHESTREATION
        );
        foreach ($operationsFiles->getIterator() as $file) {
            $config = CreateOrchestrationOperationConfig::create(
                $file->getBasename('.json'),
                JsonHelper::readFile($file->getPathname()),
                []
            );
            $operation = new CreateOrchestrationOperation($orchestrationApiClient, $logger);
            $operation->execute($config, $operationsStore);
        }

        return $operationsStore;
    }

    private function getOperationsFiles(
        string $scaffoldConfigurationFolder,
        string $operationFolder
    ): Finder {
        $finder = new Finder();
        return $finder->in($scaffoldConfigurationFolder . '/operations/' . $operationFolder . '/')
            ->files()->depth(0);
    }

    public function getSyncActions(): array
    {
        return [
            'listScaffolds' => 'actionListScaffolds',
            'useScaffold' => 'actionUseScaffold',
        ];
    }

    protected function run(): void
    {
        throw new UserException('Can be used only for sync actions {listScaffolds,useScaffold}.');
    }

    public function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }

    public function getConfigClass(): string
    {
        return Config::class;
    }
}
