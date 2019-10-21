<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\StorageApi\Client as StorageApiClient;
use Keboola\StorageApi\Components as ComponentsApiClient;
use Psr\Log\LoggerInterface;

class ApiClientStore
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

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
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
}
