<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation;

use Exception;
use Keboola\ScaffoldApp\OperationConfig\CreateCofigurationRowsOperationConfig;
use Keboola\ScaffoldApp\Operation\CreateConfigurationRowsOperation;
use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\Operation\FinishedOperationsStore;
use Keboola\StorageApi\Options\Components\Configuration;
use Keboola\StorageApi\Options\Components\ConfigurationRow;
use Psr\Log\NullLogger;

class CreateConfigurationRowsOperationTest extends BaseOperation
{
    public function testExecute(): void
    {
        $componentsApiClient = $this->getMockComponentsApiClient();
        $componentsApiClient->method('addConfigurationRow')->willReturn(['id' => 'createdRowId']);

        $operation = new CreateConfigurationRowsOperation($componentsApiClient, new NullLogger());
        $config = CreateCofigurationRowsOperationConfig::create('operationCreatedConfigurationId', [
            [
                'name' => 'row1',
                'configuration' => [],
            ],
        ], []);

        // mock finished CreateConfiguration
        $store = new FinishedOperationsStore();
        $store->add(
            'operationCreatedConfigurationId',
            CreateConfigurationOperation::class,
            (new Configuration())->setConfigurationId('1')
        );

        $operation->execute($config, $store);
        /** @var ConfigurationRow $created */
        $created = $store->getOperationData('row.operationCreatedConfigurationId.createdRowId');
        self::assertInstanceOf(ConfigurationRow::class, $created);
        self::assertSame('1', $created->getComponentConfiguration()->getConfigurationId());
    }

    public function testExecuteInvalidReference(): void
    {
        $componentsApiClient = $this->getMockComponentsApiClient();
        $operation = new CreateConfigurationRowsOperation($componentsApiClient, new NullLogger());
        $config = CreateCofigurationRowsOperationConfig::create(
            'operationCreatedConfigurationId',
            [['name' => 'row1']],
            []
        );

        // mock finished CreateConfiguration
        $store = new FinishedOperationsStore();
        $store->add('opCreateRow1', CreateConfigurationOperation::class, ['invalidData']);

        self::expectException(\Throwable::class);
        self::expectExceptionMessage(
            'Operation "operationCreatedConfigurationId" was not finished or it\'s wrongly configured.'
        );
        $operation->execute($config, $store);
    }
}
