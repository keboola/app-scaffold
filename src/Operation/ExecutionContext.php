<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Exception;
use Keboola\Component\UserException;
use Keboola\ScaffoldApp\EncryptionClient;
use Keboola\ScaffoldApp\OrchestratorClientFactory;
use Keboola\StorageApi\Client as StorageApiClient;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\StorageApi\Components as ComponentsApiClient;
use Keboola\StorageApi\Options\Components\Configuration;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ExecutionContext
{
    /**
     * @var StorageApiClient
     */
    private $storageApiClient;

    /**
     * @var OrchestratorClient
     */
    private $orchestrationApiClient;

    /**
     * @var EncryptionClient
     */
    private $encryptionApiClient;

    /**
     * @var ComponentsApiClient
     */
    private $componentsApiClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $manifest;

    /**
     * @var array
     */
    private $scaffoldInputs;

    /**
     * @var SplFileInfo
     */
    private $scaffoldFolder;

    /**
     * @var array|string[]
     */
    private $allOperations;

    /**
     * @var array|string[]
     */
    private $requiredOperations;

    /**
     * @var array|string[]
     */
    private $operationsToExecute;

    /**
     * @var string
     */
    private $scaffoldId;

    /**
     * @var array
     */
    private $operationsQueue = [];

    /**
     * @var array
     */
    private $finishedOperations;

    public function __construct(
        array $manifest,
        array $scaffoldInputs,
        string $scaffoldFolder,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->manifest = $manifest;
        $this->scaffoldInputs = $scaffoldInputs;
        $this->scaffoldFolder = new SplFileInfo($scaffoldFolder, '', '');
        $this->scaffoldId = $this->scaffoldFolder->getFilename();
    }

    /**
     * @param string $operationId
     * @param string $operationClass
     * @param mixed $data
     * @param array $userActions
     */
    public function finishOperation(
        string $operationId,
        string $operationClass,
        $data,
        array $userActions = []
    ): void {
        $this->finishedOperations[] = [
            'id' => $operationId,
            'operationClass' => $operationClass,
            'data' => $data,
            'userActions' => $userActions,
        ];
    }

    public function getComponentsApiClient(): ComponentsApiClient
    {
        if ($this->componentsApiClient === null) {
            $this->componentsApiClient = new ComponentsApiClient($this->getStorageApiClient());
        }
        return $this->componentsApiClient;
    }

    public function getStorageApiClient(): StorageApiClient
    {
        if ($this->storageApiClient === null) {
            $this->storageApiClient = new StorageApiClient(
                [
                    'token' => getenv('KBC_TOKEN'),
                    'url' => getenv('KBC_URL'),
                    'logger' => $this->logger,
                ]
            );
        }
        return $this->storageApiClient;
    }

    public function getEncryptionApiClient(): EncryptionClient
    {
        if ($this->encryptionApiClient === null) {
            $this->encryptionApiClient = EncryptionClient::createForStorageApi($this->getStorageApiClient());
        }
        return $this->encryptionApiClient;
    }

    /**
     * @return mixed
     */
    public function getFinishedOperationData(string $operationId)
    {
        foreach ($this->finishedOperations as $operation) {
            if ($operation['id'] === $operationId) {
                return $operation['data'];
            }
        }

        throw new Exception(sprintf(
            'Operation "%s" was not finished or it\'s wrongly configured.',
            $operationId
        ));
    }

    public function getFinishedOperationsResponse(): array
    {
        $response = [];
        foreach ($this->finishedOperations as $operation) {
            switch ($operation['operationClass']) {
                case CreateConfigurationOperation::class:
                    /** @var Configuration $data */
                    $data = $operation['data'];
                    $response[] = [
                        'id' => $operation['id'],
                        'configurationId' => $data->getConfigurationId(),
                        'userActions' => $operation['userActions'],
                    ];
                    break;
                case CreateOrchestrationOperation::class:
                    $response[] = [
                        'id' => $operation['id'],
                        'configurationId' => $operation['data'],
                        'userActions' => $operation['userActions'],
                    ];
                    break;
                default:
                    break;
            }
        }

        return $response;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return array
     */
    public function getOperationsQueue(): array
    {
        return $this->operationsQueue;
    }

    /**
     * @return array|string[]
     */
    public function getOperationsToExecute(): array
    {
        return $this->operationsToExecute;
    }

    public function getOrchestrationApiClient(): OrchestratorClient
    {
        if ($this->orchestrationApiClient === null) {
            $this->orchestrationApiClient = OrchestratorClientFactory::createForStorageApi(
                $this->getStorageApiClient()
            );
        }
        return $this->orchestrationApiClient;
    }

    /**
     * @return array|string[]
     */
    public function getRequiredOperations(): array
    {
        return $this->requiredOperations;
    }

    public function getScaffoldId(): string
    {
        return $this->scaffoldId;
    }

    /**
     * @return array
     */
    public function getScaffoldInputs(): array
    {
        return $this->scaffoldInputs;
    }

    public function loadOperations(): void
    {
        foreach ($this->manifest['inputs'] as $operationInput) {
            $this->allOperations[] = $operationInput['id'];
            if ($operationInput['required'] === true) {
                $this->requiredOperations[] = $operationInput['id'];
            }
        }

        foreach ($this->scaffoldInputs as $operationId => $input) {
            $this->operationsToExecute[] = $operationId;
        }

        ExecutionContextValidator::validateContext($this);
    }

    public function loadOperationsFiles(): void
    {
        foreach ([
                // order is important
                OperationsConfig::CREATE_CONFIGURATION,
                OperationsConfig::CREATE_CONFIGURATION_ROWS,
                OperationsConfig::CREATE_ORCHESTREATION,
            ] as $operationsName) {
            $operationsFileNames = array_map(function ($operation) {
                return $operation . '.json';
            }, $this->operationsToExecute);

            $finder = new Finder();
            $foundOperations = $finder->in(sprintf('%s/operations/%s/', $this->scaffoldFolder, $operationsName))
                ->files()
                ->name($operationsFileNames) // include only operations that will be executed
                ->depth(0);

            $this->operationsQueue[$operationsName] = [];

            foreach ($foundOperations->getIterator() as $operationFile) {
                $this->operationsQueue[$operationsName][] = $operationFile;
            }
        }
    }
}
