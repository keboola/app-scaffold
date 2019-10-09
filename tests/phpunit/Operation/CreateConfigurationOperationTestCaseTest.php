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

class CreateConfigurationOperationTestCaseTest extends BaseOperationTestCase
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
