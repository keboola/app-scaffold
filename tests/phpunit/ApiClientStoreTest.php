<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests;

use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\ScaffoldApp\ApiClientStore;
use Keboola\ScaffoldApp\EncryptionClient;
use Keboola\ScaffoldApp\Tests\Operation\BaseOperationTestCase;
use Keboola\StorageApi\Client as StorageApiClient;
use Keboola\StorageApi\Components as ComponentsApiClient;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class ApiClientStoreTest extends BaseOperationTestCase
{
    public function testGetComponentsApiClient(): void
    {
        $executionContext = $this->getInstance();
        self::assertInstanceOf(ComponentsApiClient::class, $executionContext->getComponentsApiClient());
    }

    private function getInstance(): ApiClientStore
    {
        return new ApiClientStore(new NullLogger);
    }

    public function testGetEncryptionApiClient(): void
    {
        /** @var ApiClientStore|MockObject $store */
        $store = self::createPartialMock(ApiClientStore::class, ['getStorageApiClient']);
        $clientMock = $this->getMockStorageApiClient();
        $clientMock->expects(self::once())->method('indexAction')->willReturn([
            'services' => [
                [
                    'id' => 'encryption',
                    'url' => 'https://url',
                ],
            ],
        ]);
        $store->expects(self::once())->method('getStorageApiClient')->willReturn($clientMock);
        self::assertInstanceOf(EncryptionClient::class, $store->getEncryptionApiClient());
        //second test is because api is not called
        self::assertInstanceOf(EncryptionClient::class, $store->getEncryptionApiClient());
    }

    public function testGetOrchestrationApiClient(): void
    {
        /** @var ApiClientStore|MockObject $executionContextMock */
        $executionContextMock = self::createPartialMock(ApiClientStore::class, ['getStorageApiClient']);
        $clientMock = $this->getMockStorageApiClient();
        $clientMock->expects(self::once())->method('indexAction')->willReturn([
            'services' => [
                [
                    'id' => 'syrup',
                    'url' => 'https://url',
                ],
            ],
        ]);
        $executionContextMock->expects(self::once())->method('getStorageApiClient')->willReturn($clientMock);
        self::assertInstanceOf(OrchestratorClient::class, $executionContextMock->getOrchestrationApiClient());
        //second test is because api is not called
        self::assertInstanceOf(OrchestratorClient::class, $executionContextMock->getOrchestrationApiClient());
    }

    public function testGetStorageApiClient(): void
    {
        $executionContext = $this->getInstance();
        self::assertInstanceOf(StorageApiClient::class, $executionContext->getStorageApiClient());
    }
}
