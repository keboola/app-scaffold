<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation;

use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\Operation\FinishedOperationsStore;
use Keboola\ScaffoldApp\OperationConfig\CreateConfigurationOperationConfig;
use Keboola\StorageApi\Options\Components\Configuration;
use Psr\Log\NullLogger;

class CreateConfigurationOperationTestCaseTest extends BaseOperationTestCase
{
    public function testExecute(): void
    {
        $sapiClient = $this->getMockStorageApiClient();
        $sapiClient->method('verifyToken')->willReturn(['owner' => ['id' => 'ownderId']]);

        $componentsApiClient = $this->getMockComponentsApiClient();
        $componentsApiClient->method('addConfiguration')->willReturn(['id' => 'createdConfigurationId']);

        $operation = new CreateConfigurationOperation(
            $sapiClient,
            $this->getMockEncryptionApiClient(),
            $componentsApiClient,
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
}
