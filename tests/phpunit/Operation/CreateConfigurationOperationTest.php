<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation;

use Keboola\ScaffoldApp\EncryptionClient;
use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\Operation\FinishedOperationsStore;
use Keboola\ScaffoldApp\OperationConfig\CreateConfigurationOperationConfig;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;

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
        $this->sapiClient->method('verifyToken')->willReturn(['owner' => ['id' => 'ownderId']]);

        $this->componentsApiClient = $this->getMockComponentsApiClient();
        $this->componentsApiClient->method('addConfiguration')->willReturn(['id' => 'createdConfigurationId']);
    }

    public function testExecute(): void
    {
        /** @var EncryptionClient|MockObject $encryptionApiClient */
        $encryptionApiClient = self::createMock(EncryptionClient::class);
        $encryptionApiClient->expects(self::once())->method('encryptConfigurationData')
            ->willReturnCallback(function (array $data) {
                return $data;
            });

        $operation = new CreateConfigurationOperation(
            $this->sapiClient,
            $encryptionApiClient,
            $this->componentsApiClient,
            new NullLogger()
        );

        $operationConfig = [
            'componentId' => 'keboola.ex.test',
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
        $store = new FinishedOperationsStore();

        $operation->execute($config, $store);
        /** @var Configuration $created */
        $created = $store->getOperationData('op1');
        self::assertInstanceOf(Configuration::class, $created);
        self::assertEquals('createdConfigurationId', $created->getConfigurationId());
        self::assertSame([
            'val1' => 'val',
            'val2' => 'val',
        ], $created->getConfiguration());
    }

    public function testExecuteAuthorizationSnowflake(): void
    {
        /** @var EncryptionClient|MockObject $encryptionApiClient */
        $encryptionApiClient = self::createMock(EncryptionClient::class);
        $encryptionApiClient->expects(self::exactly(2))->method('encryptConfigurationData')
            ->willReturnCallback(function (array $data) {
                return $data;
            });
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

        $operation = new CreateConfigurationOperation(
            $this->sapiClient,
            $encryptionApiClient,
            $this->componentsApiClient,
            new NullLogger()
        );

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

        $parameters = [
            'op1' => [
                'val2' => 'val',
            ],
        ];

        $config = CreateConfigurationOperationConfig::create('op1', $operationConfig, $parameters);
        $store = new FinishedOperationsStore();

        $operation->execute($config, $store);
        /** @var Configuration $created */
        $created = $store->getOperationData('op1');
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
                    '#password' => 'abc',
                    'port' => '443',
                    'driver' => 'snowflake',
                ],
            ],
        ], $created->getConfiguration());
    }

    public function testExecuteAuthorizationOAuth(): void
    {
        /** @var EncryptionClient|MockObject $encryptionApiClient */
        $encryptionApiClient = self::createMock(EncryptionClient::class);
        $encryptionApiClient->expects(self::once())->method('encryptConfigurationData')
            ->willReturnCallback(function (array $data) {
                return $data;
            });

        $operation = new CreateConfigurationOperation(
            $this->sapiClient,
            $encryptionApiClient,
            $this->componentsApiClient,
            new NullLogger()
        );

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
        $store = new FinishedOperationsStore();

        $operation->execute($config, $store);
        $response = $store->getOperationsResponse();
        self::assertSame([
            [
                'id' => 'op1',
                'configurationId' => 'createdConfigurationId',
                'userActions' => ['oauth'],
            ],
        ], $response);
    }

    public function testExecuteAuthorizationRedshift(): void
    {
        /** @var EncryptionClient|MockObject $encryptionApiClient */
        $encryptionApiClient = self::createMock(EncryptionClient::class);
        $encryptionApiClient->expects(self::exactly(2))->method('encryptConfigurationData')
            ->willReturnCallback(function (array $data) {
                return $data;
            });
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

        $operation = new CreateConfigurationOperation(
            $this->sapiClient,
            $encryptionApiClient,
            $this->componentsApiClient,
            new NullLogger()
        );

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
        $store = new FinishedOperationsStore();

        $operation->execute($config, $store);
        /** @var Configuration $created */
        $created = $store->getOperationData('op1');
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
                    '#password' => 'abc',
                    'port' => '5439',
                    'driver' => 'redshift',
                ],
            ],
        ], $created->getConfiguration());
    }

    public function testExecuteEmptyConfiguration(): void
    {
        /** @var EncryptionClient|MockObject $encryptionApiClient */
        $encryptionApiClient = self::createMock(EncryptionClient::class);
        $encryptionApiClient->expects(self::never())->method('encryptConfigurationData');

        $operation = new CreateConfigurationOperation(
            $this->sapiClient,
            $encryptionApiClient,
            $this->componentsApiClient,
            new NullLogger()
        );

        $operationConfig = [
            'componentId' => 'keboola.ex.test',
            'payload' => [
                'name' => 'Test Extractor',
            ],
        ];

        $parameters = [];

        $config = CreateConfigurationOperationConfig::create('op1', $operationConfig, $parameters);
        $store = new FinishedOperationsStore();

        $operation->execute($config, $store);
        /** @var Configuration $created */
        $created = $store->getOperationData('op1');
        self::assertInstanceOf(Configuration::class, $created);
        self::assertEquals('createdConfigurationId', $created->getConfigurationId());
        self::assertIsArray($created->getConfiguration());
        self::assertCount(0, $created->getConfiguration());
    }
}
