<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation;

use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\Operation\CreateConfigurationRowsOperation;
use Keboola\ScaffoldApp\Operation\CreateOrchestrationOperation;
use Keboola\ScaffoldApp\Operation\FinishedOperationsStore;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\TestCase;

class FinishedOperationsStoreTest extends TestCase
{
    public function testAdd(): void
    {
        $store = new FinishedOperationsStore();
        $store->add('id', CreateConfigurationOperation::class, ['data']);
        self::assertSame(['data'], $store->getOperationData('id'));
        self::assertCount(1, $store->getOperations());
    }

    public function testGetOperationDataMissingReference(): void
    {
        $store = new FinishedOperationsStore();
        $store->add('id', CreateConfigurationOperation::class, ['data']);

        self::expectException(\Throwable::class);
        self::expectExceptionMessage('Operation "NoId" was not finished or it\'s wrongly configured.');
        $store->getOperationData('NoId');
    }

    public function testResponse(): void
    {
        $configuration = $this->getMockBuilder(Configuration::class)
            ->setMethods(['getConfigurationId'])
            ->disableOriginalConstructor()
            ->getMock();
        $configuration->method('getConfigurationId')
            ->willReturnOnConsecutiveCalls('123', '345');
        $store = new FinishedOperationsStore();
        $store->add('id1', CreateConfigurationOperation::class, $configuration);
        $store->add('id2', CreateOrchestrationOperation::class, '567');
        $store->add('id3', CreateConfigurationRowsOperation::class, ['data3']);
        $store->add('id4', CreateConfigurationOperation::class, $configuration, ['foo', 'bar']);
        self::assertSame(
            [
                [
                    'id' => 'id1',
                    'configurationId' => '123',
                    'userActions' => [],
                ],
                [
                    'id' => 'id2',
                    'configurationId' => '567',
                    'userActions' => [],
                ],
                [
                    'id' => 'id4',
                    'configurationId' => '345',
                    'userActions' => ['foo', 'bar'],
                ],
            ],
            $store->getOperationsResponse()
        );
    }
}
