<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation;

use Keboola\ScaffoldApp\Operation\ExecutionContext;
use Keboola\ScaffoldApp\OperationConfig\CreateCofigurationRowsOperationConfig;
use Keboola\ScaffoldApp\Operation\CreateConfigurationRowsOperation;
use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\StorageApi\Options\Components\Configuration;
use Keboola\StorageApi\Options\Components\ConfigurationRow;
use PHPUnit\Framework\MockObject\MockObject;

class CreateConfigurationRowsOperationTest extends BaseOperationTestCase
{
    public function testExecute(): void
    {
        /** @var MockObject|ExecutionContext $contextMock */
        $executionMock = self::getExecutionContextMock();

        $componentsApiClient = $this->getMockComponentsApiClient();
        $componentsApiClient->method('addConfigurationRow')->willReturn(['id' => 'createdRowId']);

        $executionMock->method('getComponentsApiClient')->willReturn($componentsApiClient);

        $operation = new CreateConfigurationRowsOperation();
        $config = CreateCofigurationRowsOperationConfig::create('operationCreatedConfigurationId', [
            [
                'name' => 'row1',
                'configuration' => [],
            ],
        ], []);

        // mock finished CreateConfiguration
        $executionMock->finishOperation(
            'operationCreatedConfigurationId',
            CreateConfigurationOperation::class,
            (new Configuration())->setConfigurationId('1')
        );

        $operation->execute($config, $executionMock);
        /** @var ConfigurationRow $created */
        $created = $executionMock->getFinishedOperationData('row.operationCreatedConfigurationId.createdRowId');
        self::assertInstanceOf(ConfigurationRow::class, $created);
        self::assertSame('1', $created->getComponentConfiguration()->getConfigurationId());
    }

    public function testExecuteInvalidReference(): void
    {
        /** @var MockObject|ExecutionContext $contextMock */
        $executionMock = self::getExecutionContextMock();
        $executionMock->method('getComponentsApiClient')->willReturn($this->getMockComponentsApiClient());

        $operation = new CreateConfigurationRowsOperation();
        $config = CreateCofigurationRowsOperationConfig::create(
            'operationCreatedConfigurationId',
            [['name' => 'row1']],
            []
        );

        // mock finished CreateConfiguration
        $executionMock->finishOperation('opCreateRow1', CreateConfigurationOperation::class, ['invalidData']);

        self::expectException(\Throwable::class);
        self::expectExceptionMessage(
            'Operation "operationCreatedConfigurationId" was not finished or it\'s wrongly configured.'
        );
        $operation->execute($config, $executionMock);
    }
}
