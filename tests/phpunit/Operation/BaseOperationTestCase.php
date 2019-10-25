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
use PHPUnit\Framework\MockObject\Matcher\InvokedCount as InvokedCountMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

abstract class BaseOperationTestCase extends TestCase
{
    /**
     * @param MockObject|Client $sapiClient
     * @param MockObject|Components $componentsApiClient
     * @param MockObject|EncryptionClient $encryptionApiClient
     * @param MockObject|OrchestratorClient $orchestrationApiClient
     * @return MockObject|ApiClientStore
     */
    public function getApiClientStore(
        $sapiClient = null,
        $componentsApiClient = null,
        $encryptionApiClient = null,
        $orchestrationApiClient = null
    ) {
        /** @var MockObject|ApiClientStore $mock */
        $mock = self::getMockBuilder(ApiClientStore::class)
            ->setConstructorArgs([
                new NullLogger(),
            ])
            ->setMethods([
                'getComponentsApiClient',
                'getStorageApiClient',
                'getEncryptionApiClient',
                'getOrchestrationApiClient',
            ])
            ->getMock();

        if ($sapiClient !== null) {
            $mock->method('getStorageApiClient')->willReturn($sapiClient);
        }
        if ($encryptionApiClient !== null) {
            $mock->method('getEncryptionApiClient')->willReturn($encryptionApiClient);
        }
        if ($componentsApiClient !== null) {
            $mock->method('getComponentsApiClient')->willReturn($componentsApiClient);
        }
        if ($orchestrationApiClient !== null) {
            $mock->method('getOrchestrationApiClient')->willReturn($orchestrationApiClient);
        }

        return $mock;
    }

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
    public function getMockEncryptionApiClient(InvokedCountMatcher $matcher)
    {
        /** @var EncryptionClient|MockObject $encryptionApiMock */
        $encryptionApiMock = self::createMock(EncryptionClient::class);

        $encryptionApiMock->expects($matcher)->method('encryptConfigurationData')
            ->willReturnCallback(function (
                array $data,
                string $componentId,
                string $projectId
            ) {
                return $data;
            });

        return $encryptionApiMock;
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
