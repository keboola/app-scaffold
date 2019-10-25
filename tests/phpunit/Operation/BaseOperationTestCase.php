<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation;

use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\ScaffoldApp\ApiClientStore;
use Keboola\ScaffoldApp\EncryptionClient;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContext;
use Keboola\ScaffoldApp\Operation\OperationsQueue;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

abstract class BaseOperationTestCase extends TestCase
{
    public function getExecutionContext(
        array $manifest = [],
        array $inputs = [],
        string $scaffoldFolder = '',
        ?OperationsQueue $operationsQueue = null
    ): ExecutionContext {
        if ($operationsQueue === null) {
            $operationsQueue = new OperationsQueue();
        }

        return new ExecutionContext($manifest, $inputs, $scaffoldFolder, $operationsQueue);
    }

    /**
     * @return MockObject|ApiClientStore
     */
    public function getApiClientStore()
    {
        /** @var MockObject|ApiClientStore $contextMock */
        $contextMock = self::getMockBuilder(ApiClientStore::class)
            ->setConstructorArgs([
                new NullLogger(),
            ])
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods([
                'getComponentsApiClient',
                'getStorageApiClient',
                'getEncryptionApiClient',
                'getOrchestrationApiClient',
            ])
            ->getMock();

        return $contextMock;
    }

    /**
     * @return Components|MockObject
     */
    public function getMockComponentsApiClient()
    {
        /** @var Components|MockObject $mock */
        $mock = self::getMockBuilder(Components::class)
            ->disableOriginalConstructor()
            ->getMock();
        return $mock;
    }

    /**
     * @return EncryptionClient|MockObject
     */
    public function getMockEncryptionApiClient()
    {
        /** @var EncryptionClient|MockObject $encyptionApiMock */
        $encyptionApiMock = self::createMock(EncryptionClient::class);

        $encyptionApiMock->method('encryptConfigurationData')
            ->willReturnCallback(function (
                array $data,
                string $componentId,
                string $projectId
            ) {
                return $data;
            });

        return $encyptionApiMock;
    }

    /**
     * @return OrchestratorClient|MockObject
     */
    public function getMockOrchestrationApiClient()
    {
        /** @var OrchestratorClient|MockObject $mock */
        $mock = self::createMock(OrchestratorClient::class);
        return $mock;
    }

    /**
     * @return Client|MockObject
     */
    public function getMockStorageApiClient()
    {
        /** @var Client|MockObject $mock */
        $mock = self::getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        return $mock;
    }
}
