<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation;

use Keboola\ScaffoldApp\EncryptionClient;
use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\OperationConfig\CreateConfigurationOperationConfig;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\MockObject\MockObject;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use Psr\Log\NullLogger;

class CreateConfigurationOperationTest extends BaseOperationTestCase
{
    /**
     * @var Client|MockObject
     */
    private $sapiClient;

    /**
     * @var Components|MockObject
     */
    private $componentsApiClient;

    protected function setUp(): void
    {
        $this->sapiClient = $this->getMockStorageApiClient();
        $this->sapiClient->method('verifyToken')->willReturn(['owner' => ['id' => 'ownerId']]);

        $this->componentsApiClient = $this->getMockComponentsApiClient();
        $this->componentsApiClient->method('addConfiguration')->willReturn(['id' => 'createdConfigurationId']);
    }

    public function testExecute(): void
    {
        $parameters = [
            'op1' => [
                'val2' => 'val',
            ],
        ];

        $executionMock = self::getExecutionContext([], $parameters, 'dummy/scaffold_id');

        $apiClientStoreMock = self::getApiClientStore(
            $this->sapiClient,
            $this->componentsApiClient,
            self::getMockEncryptionApiClient(self::once()),
            null
        );

        $operation = new CreateConfigurationOperation($apiClientStoreMock, new NullLogger);

        $operationConfig = [
            'componentId' => 'keboola.ex.test',
            'payload' => [
                'name' => 'Test Extractor',
                'description' => 'Test Description',
                'configuration' => [
                    'val1' => 'val',
                ],
            ],
        ];

        $config = CreateConfigurationOperationConfig::create(
            'op1',
            $operationConfig,
            $executionMock->getScaffoldInputs()
        );

        $operation->execute($config, $executionMock);
        /** @var Configuration $created */
        $created = $executionMock->getOperationsQueue()->getFinishedOperationData('op1');
        self::assertInstanceOf(Configuration::class, $created);
        self::assertEquals('createdConfigurationId', $created->getConfigurationId());
        self::assertSame([
            'val1' => 'val',
            'val2' => 'val',
        ], $created->getConfiguration());
        self::assertSame('Test Description |ScaffoldId: scaffold_id|', $created->getDescription());
    }

    public function testExecuteAuthorizationOAuth(): void
    {
        $parameters = [
            'op1' => [
                'val2' => 'val',
            ],
        ];

        $executionMock = self::getExecutionContext([], $parameters);

        $apiClientStoreMock = self::getApiClientStore(
            $this->sapiClient,
            $this->componentsApiClient,
            self::getMockEncryptionApiClient(self::once()),
            null
        );

        $operation = new CreateConfigurationOperation($apiClientStoreMock, new NullLogger);

        $operationConfig = [
            'componentId' => 'keboola.ex.test',
            'authorization' => 'oauth',
            'payload' => [
                'name' => 'Test Extractor',
                'configuration' => [
                    'val1' => 'val',
                ],
            ],
        ];

        $parameters = [
            'op1' => [
                'val2' => 'val',
            ],
        ];

        $config = CreateConfigurationOperationConfig::create('op1', $operationConfig, $parameters);

        $operation->execute($config, $executionMock);
        $response = $executionMock->getFinishedOperationsResponse();
        self::assertSame([
            [
                'id' => 'op1',
                'userActions' => ['oauth'],
                'configurationId' => 'createdConfigurationId',
                'componentId' => 'keboola.ex.test',
            ],
        ], $response);
    }

    public function testExecuteAuthorizationRedshift(): void
    {
        $parameters = [
            'op1' => [
                'val2' => 'val',
            ],
        ];

        $executionMock = self::getExecutionContext([], $parameters);

        $apiClientStoreMock = self::getApiClientStore();
        $encryptionApiClient = self::getMockEncryptionApiClient(self::exactly(1));
        $apiClientStoreMock->method('getEncryptionApiClient')->willReturn($encryptionApiClient);
        $apiClientStoreMock->method('getComponentsApiClient')->willReturn($this->componentsApiClient);
        $this->sapiClient->method('apiPost')->willReturn([
            'connection' => [
                'backend' => 'redshift',
                'host' => 'testing.us-east-1.redshift.amazonaws.com',
                'database' => 'sapi_123',
                'schema' => 'workspace_123456',
                'user' => 'sapi_workspace_123456',
                'password' => 'abc',
            ],
            'id' => '1234',
        ]);
        $apiClientStoreMock->method('getStorageApiClient')->willReturn($this->sapiClient);

        $operation = new CreateConfigurationOperation($apiClientStoreMock, new NullLogger);

        $operationConfig = [
            'componentId' => 'keboola.ex.test',
            'authorization' => 'provisionedRedshift',
            'payload' => [
                'name' => 'Test Extractor',
                'configuration' => [
                    'val1' => 'val',
                ],
            ],
        ];

        $parameters = [
            'op1' => [
                'val2' => 'val',
            ],
        ];

        $config = CreateConfigurationOperationConfig::create('op1', $operationConfig, $parameters);

        $operation->execute($config, $executionMock);
        /** @var Configuration $created */
        $created = $executionMock->getOperationsQueue()->getFinishedOperationData('op1');
        self::assertInstanceOf(Configuration::class, $created);
        self::assertEquals('createdConfigurationId', $created->getConfigurationId());
        self::assertSame([
            'val1' => 'val',
            'val2' => 'val',
            'parameters' => [
                'db' => [
                    'host' => 'testing.us-east-1.redshift.amazonaws.com',
                    'database' => 'sapi_123',
                    'schema' => 'workspace_123456',
                    'user' => 'sapi_workspace_123456',
                    'password' => 'abc',
                    'port' => '5439',
                    'driver' => 'redshift',
                ],
            ],
        ], $created->getConfiguration());
    }

    public function testExecuteAuthorizationSnowflake(): void
    {
        $parameters = [
            'op1' => [
                'val2' => 'val',
            ],
        ];

        $executionMock = self::getExecutionContext([], $parameters);

        $this->sapiClient->method('apiPost')->willReturn([
            'connection' => [
                'backend' => 'snowflake',
                'host' => 'keboola.snowflakecomputing.com',
                'database' => 'keboola_123',
                'schema' => 'boring_wozniak',
                'warehouse' => 'SAPI_PROD',
                'user' => 'xzy',
                'password' => 'abc',
            ],
            'id' => '1234',
        ]);
        $apiClientStoreMock = self::getApiClientStore(
            $this->sapiClient,
            $this->componentsApiClient,
            self::getMockEncryptionApiClient(self::exactly(1)),
            null
        );

        $operation = new CreateConfigurationOperation($apiClientStoreMock, new NullLogger);

        $operationConfig = [
            'componentId' => 'keboola.ex.test',
            'authorization' => 'provisionedSnowflake',
            'payload' => [
                'name' => 'Test Extractor',
                'configuration' => [
                    'val1' => 'val',
                ],
            ],
        ];

        $config = CreateConfigurationOperationConfig::create('op1', $operationConfig, $parameters);

        $operation->execute($config, $executionMock);
        /** @var Configuration $created */
        $created = $executionMock->getOperationsQueue()->getFinishedOperationData('op1');
        self::assertInstanceOf(Configuration::class, $created);
        self::assertEquals('createdConfigurationId', $created->getConfigurationId());
        self::assertSame([
            'val1' => 'val',
            'val2' => 'val',
            'parameters' => [
                'db' => [
                    'host' => 'keboola.snowflakecomputing.com',
                    'database' => 'keboola_123',
                    'schema' => 'boring_wozniak',
                    'warehouse' => 'SAPI_PROD',
                    'user' => 'xzy',
                    'password' => 'abc',
                    'port' => '443',
                    'driver' => 'snowflake',
                ],
            ],
        ], $created->getConfiguration());
    }

    public function testExecuteEmptyConfiguration(): void
    {
        $parameters = [];

        $executionMock = self::getExecutionContext([], $parameters);

        $apiClientStoreMock = self::getApiClientStore(
            $this->sapiClient,
            $this->componentsApiClient,
            self::getMockEncryptionApiClient(self::never()),
            null
        );

        $operation = new CreateConfigurationOperation($apiClientStoreMock, new NullLogger);

        $operationConfig = [
            'componentId' => 'keboola.ex.test',
            'payload' => [
                'name' => 'Test Extractor',
            ],
        ];

        $parameters = [];

        $config = CreateConfigurationOperationConfig::create('op1', $operationConfig, $parameters);

        $operation->execute($config, $executionMock);
        /** @var Configuration $created */
        $created = $executionMock->getOperationsQueue()->getFinishedOperationData('op1');
        self::assertInstanceOf(Configuration::class, $created);
        self::assertEquals('createdConfigurationId', $created->getConfigurationId());
        self::assertIsArray($created->getConfiguration());
        self::assertCount(0, $created->getConfiguration());
    }
}
